<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Front end helper
 *
 * @package     AdvancedAds\Pro
 * @author      Advanced Ads <info@wpadvancedads.com>
 */

/**
 * Frontend helper class
 */
class Advanced_Ads_Geo {
	/**
	 * Look at the options and check whether they contain valid values for a lat/lon distance check
	 *
	 * @param array $options geo options.
	 *
	 * @return bool
	 */
	public static function check_for_valid_lat_lon_options( $options = [] ) {
		return isset( $options['lat'] ) && isset( $options['lon'] ) && isset( $options['distance_condition'] ) && isset( $options['distance'] ) && isset( $options['distance_unit'] )
				&& is_numeric( $options['lat'] ) && is_numeric( $options['lon'] ) && is_numeric( $options['distance'] ) && '' !== $options['distance_condition'] && '' !== $options['distance_unit'];
	}

	/**
	 * Computes the distance between the coordinates and returns the result
	 *
	 * @param float $lat1 latitude for position 1.
	 * @param float $lon1 longitude for position 1.
	 * @param float $lat2 latitude for position 2.
	 * @param float $lon2 longitude for position 2.
	 * @param float $unit unit to use.
	 *
	 * @return float
	 */
	public static function calculate_distance( $lat1, $lon1, $lat2, $lon2, $unit = 'km' ) {
		$lat1 = deg2rad( $lat1 );
		$lon1 = deg2rad( $lon1 );
		$lat2 = deg2rad( $lat2 );
		$lon2 = deg2rad( $lon2 );

		$d_lon = $lon2 - $lon1;
		$a     = pow( cos( $lat2 ) * sin( $d_lon ), 2 ) + pow( cos( $lat1 ) * sin( $lat2 ) - sin( $lat1 ) * cos( $lat2 ) * cos( $d_lon ), 2 );
		$b     = sin( $lat1 ) * sin( $lat2 ) + cos( $lat1 ) * cos( $lat2 ) * cos( $d_lon );

		$rad = atan2( sqrt( $a ), $b );
		if ( 'mi' === $unit ) {
			return $rad * 3958.755865744; // 6371.0 / 1.609344;.
		}

		return $rad * 6371.0;
	}

	/**
	 * Check geo visitor condition
	 *
	 * @param array $options visitor condition options.
	 *
	 * @return bool true if ad can be displayed.
	 * @since 1.0.0
	 */
	public static function check_geo( $options = [] ) {
		$method = Advanced_Ads_Geo_Plugin::get_current_targeting_method();

		if ( ( ! isset( $options['country'] ) && ! isset( $options['region'] ) && ! isset( $options['city'] ) )
			|| ( '' === $options['country'] && '' === $options['region'] && '' === $options['city'] ) ) {
			// maybe we got a valid lat/lon condition.
			// TODO: this check should also take place when the user creates the condition and raise warnings etc.
			$has_valid_latlon_options = self::check_for_valid_lat_lon_options( $options );
			if ( ! $has_valid_latlon_options || 'sucuri' === $method ) {
				// TODO: right now the lat/lon check is not supported for sucuri.
				return true;
			}
		}

		// switch by method.
		switch ( $method ) :
			case 'sucuri':
				return self::check_geo_sucuri( $options );
			default:
				return self::check_geo_default( $options );
		endswitch;
	}

	/**
	 * Check geo visitor condition for Sucuri header method
	 *
	 * @param array $options geo options.
	 *
	 * @return bool
	 * @since 1.2
	 */
	public static function check_geo_sucuri( $options = [] ) {
		$operator     = $options['operator'] ?? 'is';
		$country      = isset( $options['country'] ) ? trim( $options['country'] ) : '';
		$api          = Advanced_Ads_Geo_Api::get_instance();
		$country_code = Advanced_Ads_Geo_Plugin::get_sucuri_country();

		if ( 'is_not' === $operator ) {
			// check EU.
			if ( 'EU' === $country ) {
				return ! $api->is_eu_state( $country_code );
			}

			// check country.
			return $country !== $country_code;
		} else {
			// check EU.
			if ( 'EU' === $country ) {
				return $api->is_eu_state( $country_code );
			}

			// check country.
			return $country === $country_code;
		}
	}

	/**
	 *
	 * Check geo visitor condition
	 *
	 * @param array $options geo ad options.
	 *
	 * @return bool
	 * @since 1.2
	 */
	public static function check_geo_default( $options = [] ) {
		$geo_mode                 = $options['geo_mode'] ?? 'classic';
		$has_valid_latlon_options = self::check_for_valid_lat_lon_options( $options );
		$operator                 = $options['operator'] ?? 'is';
		$country                  = isset( $options['country'] ) ? trim( $options['country'] ) : '';
		$region                   = isset( $options['region'] ) ? trim( $options['region'] ) : '';
		$city                     = isset( $options['city'] ) ? trim( $options['city'] ) : '';

		$lat                = $options['lat'] ?? null;
		$lon                = $options['lon'] ?? null;
		$distance           = $options['distance'] ?? null;
		$distance_condition = $options['distance_condition'] ?? null;
		$distance_unit      = $options['distance_unit'] ?? 'km';

		$api            = Advanced_Ads_Geo_Api::get_instance();
		$ip             = $api->get_real_ip_address();
		$country_code   = '';
		$visitor_city   = '';
		$visitor_region = '';

		// get locale.
		$locale = Advanced_Ads_Geo_Plugin::get_instance()->options( 'locale', 'en' );

		// reuse already existing location information to save db requests on the same page impression.
		if ( ! $ip ) {
			if ( 'is_not' === $operator ) {
				return true;
			} else {
				return false;
			}
		} elseif ( $api->used_city_reader && $city && $api->current_city ) {
			$continent_code = $api->current_continent;
			$country_code   = $api->current_country;
			$visitor_city   = $api->current_city;
		} elseif ( $api->used_city_reader && $region && $api->current_region ) {
			$continent_code = $api->current_continent;
			$country_code   = $api->current_country;
			$visitor_region = $api->current_region;
		} elseif ( ! $city && ! $region && $api->current_country ) {
			$continent_code = $api->current_continent;
			$country_code   = $api->current_country;
		} else {
			try {
				// get correct reader.
				if ( $city || $region || $has_valid_latlon_options ) {
					$reader                = $api->get_geo_ip2_city_reader();
					$api->used_city_reader = true;
				} else {
					$reader = $api->get_geo_ip2_country_reader();
				}

				if ( $reader ) {
					// Look up the IP address.
					if ( $city || $region || $has_valid_latlon_options ) {
						try {
							$record = $reader->city( $ip );
							// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch,Squiz.Commenting.EmptyCatchComment.Missing
						} catch ( Exception $e ) {
						}
					} else {
						try {
							$record = $reader->country( $ip );
							// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch,Squiz.Commenting.EmptyCatchComment.Missing
						} catch ( Exception $e ) {
						}
					}

					if ( ! empty( $record ) ) {
						$country_code           = $record->country->isoCode;
						$api->current_country   = $country_code;
						$continent_code         = $record->continent->code;
						$api->current_continent = $continent_code;
						if ( $city ) {
							$visitor_city      = isset( $record->city->name ) ? $record->city->name : __( '(unknown city)', 'advanced-ads-pro' );
							$api->current_city = $visitor_city;
							if ( isset( $record->city->names[ $locale ] ) && $record->city->names[ $locale ] ) {
								$visitor_city      = $record->city->names[ $locale ];
								$api->current_city = $visitor_city;
							}
						}
						if ( $region ) {
							$visitor_region      = $record->subdivisions[0]->name ?? __( '(unknown region)', 'advanced-ads-pro' );
							$api->current_region = $visitor_region;
							if ( isset( $record->subdivisions[0]->names[ $locale ] ) && $record->subdivisions[0]->names[ $locale ] ) {
								$visitor_region      = $record->subdivisions[0]->names[ $locale ];
								$api->current_region = $visitor_region;
							}
						}
						if ( isset( $record->location->longitude ) && isset( $record->location->latitude ) ) {
							$api->current_lat = $record->location->latitude;
							$api->current_lon = $record->location->longitude;
						}
					}
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'Advanced Ads Geo: ' . __( 'Geo Databases not found', 'advanced-ads-pro' ) );
				}
			} catch ( Exception $e ) {
				if ( defined( 'ADVANCED_ADS_GEO_CHECK_DEBUG' ) ) {
					/* translators: an error message. */
					$log_content = sprintf( __( 'Address not found: %s', 'advanced-ads-pro' ), $e->getMessage() ) . "\n";
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $log_content, 3, WP_CONTENT_DIR . '/geo-check.log' );
				}

				return false;
			}
		}

		// convert to lower case.
		if ( function_exists( 'mb_strtolower' ) ) {
			$city           = mb_strtolower( $city, 'utf-8' );
			$region         = mb_strtolower( $region, 'utf-8' );
			$visitor_city   = mb_strtolower( $visitor_city, 'UTF-8' );
			$visitor_region = mb_strtolower( $visitor_region, 'UTF-8' );
		}

		if ( defined( 'ADVANCED_ADS_GEO_CHECK_DEBUG' ) ) {
			$log_content = "GEO CHECK (setting|visitor): COUNTRY {$country}|{$country_code} – REGION {$region}|{$visitor_region} – CITY {$city}|{$visitor_city}\n";
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $log_content, 3, WP_CONTENT_DIR . '/geo-check.log' );
		}

		// set up data for continent search.
		if ( 0 === strpos( $country, 'CONT_' ) ) {
			$country_code = 'CONT_' . $continent_code;
		}

		if ( 'latlon' === $geo_mode ) {
			if ( $has_valid_latlon_options ) {
				$dst = self::calculate_distance( $api->current_lat, $api->current_lon, $lat, $lon, $distance_unit );
				if ( 'gt' === $distance_condition ) {
					return $dst > $distance;
				}

				return $dst <= $distance;
			}

			return true;
		} elseif ( 'is_not' === $operator ) {
			if ( $city ) {
				// check city.
				return $city !== $visitor_city;
			} elseif ( $region ) {
				// check region.
				return $region !== $visitor_region;
			}
			// check EU.
			if ( 'EU' === $country ) {
				return ! $api->is_eu_state( $country_code );
			}

			// check country.
			return $country !== $country_code;
		} else {
			// check city.
			if ( $city ) {
				return $city === $visitor_city;
			} elseif ( $region ) {
				return $region === $visitor_region;
			}
			// check EU.
			if ( 'EU' === $country ) {
				return $api->is_eu_state( $country_code );
			}

			// check country.
			return $country === $country_code;
		}
	}

	/**
	 * Get geo information to use in passive cache-busting.
	 */
	public static function get_passive() {
		$method = Advanced_Ads_Geo_Plugin::get_current_targeting_method();

		// switch by method.
		switch ( $method ) :
			case 'sucuri':
				return self::get_passive_sucuri();
			default:
				return self::get_passive_default();
		endswitch;
	}

	/**
	 * Get geo information to use in passive cache-busting.
	 * Sucuri header method
	 *
	 * @since 1.2
	 */
	public static function get_passive_sucuri() {
		$api          = Advanced_Ads_Geo_Api::get_instance();
		$country_code = Advanced_Ads_Geo_Plugin::get_sucuri_country();

		return [
			'country_code' => $country_code,
			'is_eu_state'  => $api->is_eu_state( $country_code ),
			'is_sucuri'    => true,
		];
	}

	/**
	 *  Get geo information to use in passive cache-busting.
	 *  Default method.
	 *
	 * @param array $options geo ad options.
	 *
	 * @return array
	 */
	public static function get_passive_default( $options = [] ) {
		global $locale;
		$api = Advanced_Ads_Geo_Api::get_instance();
		$ip  = $api->get_real_ip_address();

		// get locale.
		$options = Advanced_Ads_Geo_Plugin::get_instance()->options( 'locale', 'en' );

		$r = [];

		if ( ! $ip ) {
			return $r;
		}
		// reuse already existing location information to save db requests on the same page impression.
		if ( ! $api->used_city_reader || ! $api->current_city || ! $api->current_region || ! $api->current_country ) {
			try {
				$reader                = $api->get_geo_ip2_city_reader();
				$api->used_city_reader = true;

				if ( $reader ) {
					try {
						$record = $reader->city( $ip );
					} catch ( Exception $e ) {
						return $r;
					}

					if ( ! empty( $record ) ) {
						$api->current_country   = $record->country->isoCode;
						$api->current_continent = $record->continent->code;

						$api->current_city = isset( $record->city->name ) ? $record->city->name : __( '(unknown city)', 'advanced-ads-pro' );
						if ( isset( $record->city->names[ $locale ] ) && $record->city->names[ $locale ] ) {
							$api->current_city = $record->city->names[ $locale ];
						}
						$api->current_region = $record->subdivisions[0]->name ?? __( '(unknown region)', 'advanced-ads-pro' );
						if ( isset( $record->subdivisions[0]->names[ $locale ] ) && $record->subdivisions[0]->names[ $locale ] ) {
							$api->current_region = $record->subdivisions[0]->names[ $locale ];
						}
						if ( isset( $record->location->longitude ) && isset( $record->location->latitude ) ) {
							$api->current_lat = $record->location->latitude;
							$api->current_lon = $record->location->longitude;
						}
					}
				} else {
					return $r;
				}
			} catch ( Exception $e ) {
				return $r;
			}
		}

		$r['visitor_city']   = $api->current_city;
		$r['visitor_region'] = $api->current_region;
		$r['country_code']   = $api->current_country;
		$r['continent_code'] = $api->current_continent;
		$r['is_eu_state']    = $api->is_eu_state( $api->current_country );
		$r['current_lat']    = $api->current_lat;
		$r['current_lon']    = $api->current_lon;

		return $r;
	}
}
