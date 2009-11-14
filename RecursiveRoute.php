<?php
class RecursiveRoute_InvalidArgument_Exception extends InvalidArgumentException {}

/**
 * @TODO validators
 * @TODO defaults
 * @TODO match callback
 * @TODO subroutes restricted to certain param values (isn't that same as validators, no)
 * 
 * @TODO urlencode/htmlencode = createFilters filters
 *
 *
 *
 */
class RecursiveRoute
{
    const SEPARATOR = '/';

    /**
     * The configuration pattern the Router is constructed with
     *
     * @var string
     */
	private $pattern = '';

    /**
     * Tells it this is the root-route or a subroute.
     * Will be set by addRoute()
     * 
     * @var boolean
     */
    private $isSubRoute = false;

    /**
     * List of optional regexp validator patterns for variables
     * 
     * @var array 
     */
    protected $validators = array();

    /**
     * List of optional default values for variables.
     * If a variable has a default value and is omitted when creating an url
     * this router will match.
     *
     * @var array
     */
    protected $defaults = array();

    /**
     * The configuration pattern exploded into parts
     *
     * @var array
     */
	private $patternParts;

    /**
     * List of subRouters in the order they were added
     *
     * @var array
     */
	private $subRoutes = array();

    /**
     * All the variables defined in the pattern
     *
     * @var array
     */
	private $definedParamList;

    /**
     * All the variables that are required
     * 
     * @var array
     */
    private $requiredParamList;


    /**
     * 
     *
     * @param string $pattern
     */
	public function __construct($pattern='') {
		$this->pattern = $pattern;
        $this->patternParts = $this->explode($pattern);
		
        $this->buildParamLists($this->patternParts);
	}


    /**
     * Will add a subroute to this route
     *
     * @param $route
     */
	public function addRoute( $route ) {
        if ( is_string($route) ) {
            $route = new RecursiveRoute($route);
        }
        if (!($route instanceof RecursiveRoute)) {
            throw new RecursiveRoute_InvalidArgument_Exception(
                __METHOD__.': Arguement should be string or RecursiveRoute object'
            );
        }
        $route->setSubRoute();
		array_push($this->subRoutes, $route);
	}


    /**
     * Will set validators.
     *
     * @author [tb] 2009 oct 28
     * @param array $validators
     */
    public function setValidators(array $validators) {
        $this->validators = $validators;
    }
    

    /**
     * Will parse a given url and return an array containing parameters
     *
     * @param string $url
     * @return array
     */
	public function parseUrl( $url ) {
		$urlParts = $this->explode( $url );

        // the array holding params parsed by this router
		$collectedParams = array();

        // only self collect params if parseMatch
        // (Only occcurs in root route as this method is not called on
        // subroutes if they're not a match)
        if ($this->isParseMatch($url)) {
            for ( $i=0; $i<count($this->patternParts); $i++ ) {
                $partPattern = $this->patternParts[$i];
                $partUrl = $urlParts[$i];

                if ($partPattern[0] === ':') {
                    $paramName = substr($partPattern, 1);
                    $collectedParams[$paramName] = $partUrl;
                }
            }
        }

        // construct partial url that gets passed to subroutes
		$urlRemainingParts = array_slice($urlParts, count($this->patternParts));
		$urlRemaining = implode('/', $urlRemainingParts);

		$collectedSubRouteParams = array();
		// search subRoutes, if match then call parseUrl on that one with the
        // remaining part of this url
        $subRouteMatched = false;
        $subRoutes = array_reverse($this->subRoutes);
        foreach ($subRoutes as $subRoute) {
            if ( $subRoute->isParseMatch($urlRemaining)) {
                $collectedSubRouteParams = $subRoute->parseUrl($urlRemaining);
                $subRouteMatched = true;
                break;
            }
        }

        // if no subroute is able to handle further parsing, extract the remaining
        // url parts as key/value pairs.
        if ($subRouteMatched !== true) {
            for($i=0; $i<count($urlRemainingParts); $i=$i+2) {
                if(
                    isset($urlRemainingParts[$i]) &&
                    isset($urlRemainingParts[$i+1]) &&
                    !is_numeric($urlRemainingParts[$i])
                ) {
                    // store in subrouteparams
                    $collectedSubRouteParams[$urlRemainingParts[$i]] = $urlRemainingParts[$i+1];
                }
            }
        }
        
		return array_merge($collectedParams, $collectedSubRouteParams);
	}


    /**
     * Will create an url of the given set of parameters
     *
     * @param array $params
     * @return string
     */
	public function createUrl($params = array() ) {
        if (!is_array($params)) {
            throw new RecursiveRoute_InvalidArgument_Exception(
                __METHOD__.', Invalid argument: '.var_export($params,true)
            );
        }

        // no problem if not a create match (delegate to children) unless
        // this route has a defined pattern and no params are given.
        if (
            !$this->isCreateMatch($params) &&
            count($params) < count($this->requiredParamList)
        ) {
            throw new RecursiveRoute_InvalidArgument_Exception(
                __METHOD__.', Missing required array keys for creating url'
            );
        }

        // call recursive method that creates url parts
        $collectResult = $this->collectUrlParts($params);
        $urlParts = $collectResult['parts'];
        $paramsProcessed = $collectResult['paramsProcessed'];

        // add any unprocessed param as last url part
        $paramsNotProcessed = $params;
        foreach($paramsProcessed as $key) {
            unset($paramsNotProcessed[$key]);
        }
        $unprocParamUrlPart = $this->constructUrlFromExcessParams($paramsNotProcessed);
        $urlParts[] = $unprocParamUrlPart['parts'];

        // create url
        $urlChunks = array();
        foreach($urlParts as $part) {
            if (count($part)) {
                $urlChunks[] = implode(self::SEPARATOR,$part);
            }
        }

        $url = implode(self::SEPARATOR,$urlChunks);
        if ($url!='' && $url!='/') {
            $url = self::SEPARATOR . $url . self::SEPARATOR;
        }
        return $url;
    }


    /**
     * If this route is a parse match for the parameters given it will add
     * an url part and recursively call collectUrlParts on all subroutes
     *
     * @param array $paramsLeft
     * @return array
     */
    protected function collectUrlParts(array $paramsLeft) {
        $currentUrlParts = array();
        $paramsProcessed = array();
        if( $this->isCreateMatch($paramsLeft)) {
            $constructResult = $this->constructUrl($paramsLeft);

            $constructedPart = $constructResult['parts'];
            $paramsProcessed = $constructResult['paramsProcessed'];
            
            // only add if not empty (likely the 'root' Route)
            if (count($constructedPart) != 0) {
                $currentUrlParts[] = $constructedPart;
            }

            // remove tags covered in this route from the list
            $truncatedParamsLeft = $paramsLeft;
            foreach ($this->definedParamList as $paramName) {
                unset($truncatedParamsLeft[$paramName]);
            }

            // iterate over routes
            $subRouteMatched = false;
            $subRoutes = array_reverse($this->subRoutes);
            foreach ($subRoutes as $subRoute) {
                $collectResult = $subRoute->collectUrlParts($truncatedParamsLeft);
                $returnedUrlParts = $collectResult['parts'];
                $paramsProcessed = array_merge(
                    $paramsProcessed,
                    $collectResult['paramsProcessed']
                );
                if (count($returnedUrlParts)>0) {
                    foreach( $returnedUrlParts as $part ) {
                        $currentUrlParts[] = $part;
                    }
                    $subRouteMatched = true;
                    break;
                }
            }
        }

        $return = array(
            'parts' => $currentUrlParts,
            'paramsProcessed' => $paramsProcessed
        );
        return $return;
    }


    /**
     * Determines if this Route is a match for creating a partial url.
     * A Route is only then a match if all the required parameters
     * are provided in $paramHash
     *
     * Exception: If this route doesn't have any required parts, has subRoutes,
     * and is not the root route, at least one of the children has to match for this route
     * to be considered a match
     *
     * @param array $paramHash
     * @return boolean
     */
    protected function isCreateMatch($paramHash) {
        if (
            $this->isSubRoute==true &&
            count($this->subRoutes)>0 &&
            count($this->requiredParamList)==0
        ) {
            $match = false;
            $subRoutes = array_reverse($this->subRoutes);
            foreach ($subRoutes as $subRoute) {
                if ($subRoute->isCreateMatch($paramHash)) {
                    $match = true;
                    break;
                }
            }
        } else {
           	$match = true;
            foreach($this->requiredParamList as $paramName) {
                if( !isset($paramHash[$paramName])) {
                    $match = false;
                }
            }
        }
        return $match;
    }


    /**
     * Will determine if this route is a match for parsing.
     * A route is a parse match if the given url is at least as long and if
     * all non variable url parts match that of the pattern.
     *
     * @TODO if not root router and itself has no required parts,
     * it should delegate to subroutes.
     *
     * @param string $url
     * @return boolean
     */
	protected function isParseMatch($url = null) {
        // @TODO check this
		if (count($this->patternParts)==0 && count($this->subRoutes)==0) return false;
//        if ($this->pattern=='' && count($this->subRoutes) <=0 ) {
//			return false;
//		}

		$urlParts = $this->explode($url);

		// check length, if shorter: no match
		if (count($urlParts) < count($this->patternParts) ) {
			return false;
		}

		// check separate params
		$match = true;
        if( count($this->patternParts) > 0 ) {
			for ( $i=0; $i<count($this->patternParts); $i++ ) {
				$partPattern = $this->patternParts[$i];
				$partUrl = $urlParts[$i];

				if ( $partPattern[0] === ':') {
					// param
				} elseif ($partPattern !== $partUrl) {
					$match = false;
					break;
				}
			}
		}

		return $match;
	}


    /**
     * Creates an url as defined by the configuration pattern and the values
     * given by $params
     *
     * @TODO url encode
     *
     * @param array $params
     * @return string
     */
	protected function constructUrl($params = array()) {
        $constructedUrlParts = array();
        $paramsProcessed = array();
        foreach($this->patternParts as $part) {
            if ($part[0] === ':') {
					// variable, get it from params
                    $paramName = substr($part, 1);
                    $constructedUrlParts[] = isset($params[$paramName]) ? $params[$paramName] : '';
                    $paramsProcessed[] = $paramName;
				} else {
                    // simply add
                    $constructedUrlParts[] = $part;
				}
        }

        $return = array(
            'parts' => $constructedUrlParts,
            'paramsProcessed' => $paramsProcessed
        );

        return $return;
	}


    /**
     * Creates an url of excess params as key/value combinations
     *
     * @TODO url encode
     *
     * @param array $params
     * @return array
     */
	protected function constructUrlFromExcessParams($params = array()) {
        $constructedUrlParts = array();
        $paramsProcessed = array();

        foreach($params as $key=>$value) {
            $constructedUrlParts[] = $key;
            $constructedUrlParts[] = $value;
            $paramsProcessed[] = $key;
        }

        $return = array(
            'parts' => $constructedUrlParts,
            'paramsProcessed' => $paramsProcessed
        );

        return $return;
	}


    /**
     * Marks the route object as being a subroute
     *
     * @param boolean $value
     */
    protected function setSubRoute($value=true) {
        $this->isSubRoute = (boolean) $value;
    }


    /**
     * Will turn the given (partial) url into separate parts
     *
     * @param string $urlPart
     * @return array
     */
	protected function explode($urlPart) {
		$urlTrimmed = trim(trim($urlPart, self::SEPARATOR));

		$parts = array();
		if($urlTrimmed!='') {
			$parts = explode(self::SEPARATOR, $urlTrimmed);
		}

		return $parts;
	}


    /**
     * Will fill some member vars with lists defining the configured variables
     * 
     * @param array $pattern_parts
     */
	protected function buildParamLists( $pattern_parts = array() ) {
		$arr_params = array();
		foreach ($pattern_parts as $part_pattern ) {
			if ( $part_pattern[0] === ':' ) {
				$arr_params[] = substr( $part_pattern, 1 );
			}
		}

        $this->definedParamList = $arr_params;
        $this->requiredParamList = $arr_params;
	}
}