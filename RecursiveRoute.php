<?php
/**
 * @TODO validators
 * @TODO defaults
 * @TODO subroutes restricted to certain param values
 * @TODO match callback
 * 
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
     * @param RecursiveRoute $route
     */
	public function addRoute( RecursiveRoute $route ) {
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
		if ( !$this->isParseMatch( $url ) ) {
			return array();
		}

		$urlParts = $this->explode( $url );

        // the array holding params parsed by this router
		$collectedParams = array();

		for ( $i=0; $i<count($this->patternParts); $i++ ) {
			$partPattern = $this->patternParts[$i];
			$partUrl = $urlParts[$i];

			if ($partPattern[0] === ':') {
				$paramName = substr($partPattern, 1);
				$collectedParams[$paramName] = $partUrl;
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
        $urlParts = $this->collectUrlParts($params);
        $url = self::SEPARATOR
             . implode(self::SEPARATOR,$urlParts)
             . self::SEPARATOR;

        return $url;
    }


    /**
     * If this route is a parse match for the parameters given it will add
     * an url part and recursively call collectUrlParts on all subroutes
     *
     * @param array $paramsLeft
     * @return array
     */
    private function collectUrlParts(array $paramsLeft) {
        $currentUrlParts = array();
        if( $this->isCreateMatch($paramsLeft)) {
            $constructedPart = $this->constructUrl($paramsLeft);
            // only add if not empty (likely the 'root' Route)
            if ($constructedPart != '') {
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
                $returnedUrlParts = $subRoute->collectUrlParts($truncatedParamsLeft);
                if (count($returnedUrlParts)>0) {
                    foreach( $returnedUrlParts as $part ) {
                        $currentUrlParts[] = $part;
                    }
                    $subRouteMatched = true;
                    break;
                }
            }

            // if no subroute has added an url part we're left with some params
            // -> add as key1/value1/key2/value2 url part
            // @TODO add urlencode
            if ($subRouteMatched !== true) {
                foreach($truncatedParamsLeft as $key=>$value) {
                    $currentUrlParts[] = $key . self::SEPARATOR . $value;
                }
            }
        }

        return $currentUrlParts;
    }


    /**
     * Determines if this Route is a match for creating a partial url.
     * A Route is only then a match if all the required parameters
     * are provided in $paramHash
     *
     * @param array $paramHash
     * @return boolean
     */
    private function isCreateMatch($paramHash) {
       	$match = true;
		foreach($this->requiredParamList as $paramName) {
			if( !isset($paramHash[$paramName])) {
				$match = false;
			}
		}
        return $match;
    }


    /**
     * Will determine if this route is a match for parsing.
     * A route is a parse match if the given url is at least as long and if
     * all non variable url parts match that of the pattern.
     *
     * @param string $url
     * @return boolean
     */
	private function isParseMatch($url = null) {
        // @TODO check this
		if ($this->pattern=='' && count($this->subRoutes) <=0 ) {
			return false;
		}

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
        foreach($this->patternParts as $part) {
            if ($part[0] === ':') {
					// variable, get it from params
                    $paramName = substr($part, 1);
                    $constructedUrlParts[] = isset($params[$paramName]) ? $params[$paramName] : '';
				} else {
                    // simply add
                    $constructedUrlParts[] = $part;
				}
        }
        $constructedUrl = implode(self::SEPARATOR, $constructedUrlParts);

        return $constructedUrl;
	}


    /**
     * Will turn the given (partial) url into separate parts
     *
     * @param string $urlPart
     * @return array
     */
	private function explode($urlPart) {
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
	private function buildParamLists( $pattern_parts = array() ) {
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