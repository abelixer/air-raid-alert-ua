<?php
/**
 * Region list repository: remote scrape, cache, and bundled fallback.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads regions from alerts.na-agency.com/regions and normalizes them.
 */
class ARA_Regions {

	/**
	 * Remote source.
	 */
	const SOURCE = 'https://alerts.na-agency.com/regions';

	/**
	 * Cache key for the normalized list.
	 */
	const CACHE_KEY = 'ara_regions_cache_v1';

	/**
	 * Persistent data key.
	 */
	const DATA_KEY = 'ara_regions_data_v1';

	/**
	 * Per-request runtime cache.
	 *
	 * @var array|null
	 */
	private static $runtime = null;

	/**
	 * Get the region list.
	 *
	 * Front-end requests never trigger a remote fetch when persistent data or a
	 * bundled snapshot exists. The daily cron job refreshes the remote list.
	 *
	 * @param bool $force Force a remote refresh.
	 * @return array
	 */
	public static function get_regions( $force = false ) {
		if ( ! $force && is_array( self::$runtime ) ) {
			return self::$runtime;
		}

		if ( ! $force ) {
			$cached = self::cache_get();
			if ( is_array( $cached ) && ! empty( $cached ) ) {
				self::$runtime = $cached;
				return $cached;
			}

			$data = self::data_get();
			if ( ! empty( $data['regions'] ) && is_array( $data['regions'] ) ) {
				self::$runtime = $data['regions'];
				self::cache_set( $data['regions'] );
				return $data['regions'];
			}

			$snapshot = self::get_snapshot();
			if ( ! empty( $snapshot ) ) {
				self::$runtime = $snapshot;
				return $snapshot;
			}
		}

		$result = self::fetch_and_store();

		if ( is_array( $result ) && ! empty( $result ) ) {
			self::$runtime = $result;
			return $result;
		}

		$data = self::data_get();
		if ( ! empty( $data['regions'] ) && is_array( $data['regions'] ) ) {
			self::$runtime = $data['regions'];
			return $data['regions'];
		}

		self::$runtime = self::get_snapshot();

		return self::$runtime;
	}

	/**
	 * Force a refresh.
	 *
	 * @return array{success:bool,message:string,count:int}
	 */
	public static function refresh() {
		self::$runtime = null;
		$result         = self::fetch_and_store();

		if ( is_array( $result ) && ! empty( $result ) ) {
			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: number of regions */
					__( 'Region list refreshed: %d entries.', 'air-raid-alert-ua' ),
					count( $result )
				),
				'count'   => count( $result ),
			);
		}

		$data = self::data_get();
		if ( ! empty( $data['regions'] ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %d: number of cached regions */
					__( 'Could not reach the region source. Kept %d cached entries.', 'air-raid-alert-ua' ),
					count( $data['regions'] )
				),
				'count'   => count( $data['regions'] ),
			);
		}

		$snapshot = self::get_snapshot();
		if ( ! empty( $snapshot ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %d: number of bundled regions */
					__( 'Could not reach the region source. Using the bundled snapshot of %d entries.', 'air-raid-alert-ua' ),
					count( $snapshot )
				),
				'count'   => count( $snapshot ),
			);
		}

		return array(
			'success' => false,
			'message' => __( 'Could not reach the region source and no cached region list is available.', 'air-raid-alert-ua' ),
			'count'   => 0,
		);
	}

	/**
	 * Cron callback.
	 *
	 * @return void
	 */
	public static function refresh_cron() {
		if ( is_multisite() ) {
			$lock = get_site_transient( 'ara_regions_refresh_lock' );
			if ( $lock ) {
				return;
			}
			set_site_transient( 'ara_regions_refresh_lock', 1, HOUR_IN_SECONDS );
		}

		self::refresh();
	}

	/**
	 * Clear all region caches.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		self::$runtime = null;
		self::cache_delete();
		self::data_delete();
	}

	/**
	 * Region IDs only.
	 *
	 * @return int[]
	 */
	public static function get_region_ids() {
		$ids = array();
		foreach ( self::get_regions() as $region ) {
			$ids[] = (int) $region['id'];
		}

		return $ids;
	}

	/**
	 * Find a region by ID.
	 *
	 * @param int $id Region ID.
	 * @return array|null
	 */
	public static function get_by_id( $id ) {
		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}

		foreach ( self::get_regions() as $region ) {
			if ( (int) $region['id'] === $id ) {
				return $region;
			}
		}

		return null;
	}

	/**
	 * Build the canonical public link for a region.
	 *
	 * @param int $id Region ID.
	 * @return string
	 */
	public static function get_region_link( $id ) {
		$region = self::get_by_id( $id );
		if ( ! $region ) {
			return 'https://alerts.na-agency.com/regions';
		}

		return 'https://alerts.na-agency.com/region/' . (int) $region['id'] . '-' . $region['slug'];
	}

	/**
	 * Regions grouped by oblast.
	 *
	 * @return array
	 */
	public static function get_grouped() {
		$regions = self::get_regions();
		$by_id   = array();
		$groups  = array();

		foreach ( $regions as $region ) {
			$by_id[ (int) $region['id'] ] = $region;
		}

		foreach ( $regions as $region ) {
			if ( 'oblast' !== $region['level'] ) {
				continue;
			}
			$groups[ (int) $region['id'] ] = array(
				'oblast'   => $region,
				'raions'   => array(),
				'hromadas' => array(),
			);
		}

		foreach ( $regions as $region ) {
			if ( 'oblast' === $region['level'] ) {
				continue;
			}

			$oblast_id = 0;
			if ( 'raion' === $region['level'] ) {
				$oblast_id = (int) $region['parent'];
			} elseif ( 'hromada' === $region['level'] ) {
				$parent = isset( $by_id[ (int) $region['parent'] ] ) ? $by_id[ (int) $region['parent'] ] : null;
				if ( $parent ) {
					$oblast_id = 'raion' === $parent['level'] ? (int) $parent['parent'] : (int) $parent['id'];
				}
			}

			if ( empty( $groups[ $oblast_id ] ) ) {
				continue;
			}

			if ( 'raion' === $region['level'] ) {
				$groups[ $oblast_id ]['raions'][ (int) $region['id'] ] = array(
					'raion'    => $region,
					'hromadas' => array(),
				);
			} elseif ( 'hromada' === $region['level'] ) {
				$parent_id = (int) $region['parent'];
				if ( isset( $groups[ $oblast_id ]['raions'][ $parent_id ] ) ) {
					$groups[ $oblast_id ]['raions'][ $parent_id ]['hromadas'][] = $region;
				} else {
					$groups[ $oblast_id ]['hromadas'][] = $region;
				}
			}
		}

		foreach ( $groups as &$group ) {
			uasort( $group['raions'], array( __CLASS__, 'sort_raions' ) );
			usort( $group['hromadas'], array( __CLASS__, 'sort_regions' ) );
			foreach ( $group['raions'] as &$raion ) {
				usort( $raion['hromadas'], array( __CLASS__, 'sort_regions' ) );
			}
			unset( $raion );
		}
		unset( $group );

		uasort( $groups, array( __CLASS__, 'sort_groups' ) );

		return $groups;
	}

	/**
	 * Render <option> markup grouped by oblast.
	 *
	 * @param int         $selected    Selected region ID.
	 * @param string|null $empty_label Optional label for an empty (inherit) option.
	 * @return string
	 */
	public static function get_select_options_html( $selected = 0, $empty_label = null ) {
		$selected = absint( $selected );
		$groups   = self::get_grouped();

		if ( null === $empty_label ) {
			$html = '<option value="0"' . selected( 0, $selected, false ) . '>' . esc_html__( '— Select a region —', 'air-raid-alert-ua' ) . '</option>';
		} else {
			$html = '<option value=""' . ( 0 === $selected ? ' selected="selected"' : '' ) . '>' . esc_html( $empty_label ) . '</option>';
		}

		foreach ( $groups as $group ) {
			$oblast = $group['oblast'];
			$html  .= '<optgroup label="' . esc_attr( $oblast['name'] ) . '">';
			$html  .= '<option value="' . (int) $oblast['id'] . '"' . selected( (int) $oblast['id'], $selected, false ) . '>' . esc_html( $oblast['name'] ) . '</option>';

			foreach ( $group['raions'] as $raion ) {
				$html .= '<option value="' . (int) $raion['raion']['id'] . '"' . selected( (int) $raion['raion']['id'], $selected, false ) . '>— ' . esc_html( $raion['raion']['name'] ) . '</option>';
				foreach ( $raion['hromadas'] as $hromada ) {
					$html .= '<option value="' . (int) $hromada['id'] . '"' . selected( (int) $hromada['id'], $selected, false ) . '>— — ' . esc_html( $hromada['name'] ) . '</option>';
				}
			}

			foreach ( $group['hromadas'] as $hromada ) {
				$html .= '<option value="' . (int) $hromada['id'] . '"' . selected( (int) $hromada['id'], $selected, false ) . '>— ' . esc_html( $hromada['name'] ) . '</option>';
			}

			$html .= '</optgroup>';
		}

		return $html;
	}

	/**
	 * Metadata about the last successful refresh.
	 *
	 * @return array
	 */
	public static function get_last_refresh_info() {
		return self::data_get();
	}

	/**
	 * Sort callback for groups.
	 *
	 * @param array $a First group.
	 * @param array $b Second group.
	 * @return int
	 */
	public static function sort_groups( $a, $b ) {
		return self::compare_names( $a['oblast']['name'], $b['oblast']['name'] );
	}

	/**
	 * Sort callback for raions.
	 *
	 * @param array $a First raion wrapper.
	 * @param array $b Second raion wrapper.
	 * @return int
	 */
	public static function sort_raions( $a, $b ) {
		return self::compare_names( $a['raion']['name'], $b['raion']['name'] );
	}

	/**
	 * Sort callback for regions.
	 *
	 * @param array $a First region.
	 * @param array $b Second region.
	 * @return int
	 */
	public static function sort_regions( $a, $b ) {
		return self::compare_names( $a['name'], $b['name'] );
	}

	/**
	 * Compare Ukrainian labels with intl when available.
	 *
	 * @param string $a First label.
	 * @param string $b Second label.
	 * @return int
	 */
	private static function compare_names( $a, $b ) {
		static $collator = null;
		static $checked  = false;

		if ( ! $checked ) {
			$checked = true;
			if ( class_exists( 'Collator' ) ) {
				try {
					$candidate = Collator::create( 'uk_UA' );
				} catch ( Throwable $exception ) {
					$candidate = null;
				}

				if ( $candidate instanceof Collator ) {
					$collator = $candidate;
				}
			}
		}

		if ( $collator ) {
			$result = $collator->compare( (string) $a, (string) $b );
			if ( is_int( $result ) ) {
				return $result;
			}
		}

		return strnatcasecmp( (string) $a, (string) $b );
	}

	/**
	 * Fetch, parse, and persist the region list.
	 *
	 * @return array|WP_Error
	 */
	private static function fetch_and_store() {
		$data    = self::data_get();
		$headers = array(
			'Accept'          => 'text/html,application/xhtml+xml',
			'Accept-Language' => 'uk,en;q=0.8',
		);

		if ( ! empty( $data['etag'] ) ) {
			$headers['If-None-Match'] = $data['etag'];
		}
		if ( ! empty( $data['last_modified'] ) ) {
			$headers['If-Modified-Since'] = $data['last_modified'];
		}

		$response = wp_remote_get(
			self::SOURCE,
			array(
				'timeout'     => 20,
				'redirection' => 3,
				'headers'     => $headers,
				'user-agent'  => 'AirRaidAlertUA/' . ARA_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 304 === $code && ! empty( $data['regions'] ) ) {
			self::cache_set( $data['regions'] );
			return $data['regions'];
		}

		if ( 200 !== $code ) {
			return new WP_Error(
				'ara_regions_http',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Region source returned HTTP %d.', 'air-raid-alert-ua' ),
					$code
				)
			);
		}

		$body    = wp_remote_retrieve_body( $response );
		$regions = self::parse_html( $body );

		if ( empty( $regions ) ) {
			return new WP_Error( 'ara_regions_parse', __( 'The region page could not be parsed.', 'air-raid-alert-ua' ) );
		}

		$payload = array(
			'generated'     => gmdate( 'c' ),
			'source'        => self::SOURCE,
			'count'         => count( $regions ),
			'regions'       => $regions,
			'etag'          => (string) wp_remote_retrieve_header( $response, 'etag' ),
			'last_modified' => (string) wp_remote_retrieve_header( $response, 'last-modified' ),
			'fetched'       => time(),
		);

		self::data_update( $payload );
		self::cache_set( $regions );

		return $regions;
	}

	/**
	 * Parse the /regions HTML.
	 *
	 * @param string $html Raw HTML.
	 * @return array
	 */
	public static function parse_html( $html ) {
		$html = (string) $html;
		if ( '' === trim( $html ) ) {
			return array();
		}

		if ( class_exists( 'DOMDocument' ) ) {
			$regions = self::parse_with_dom( $html );
			if ( ! empty( $regions ) ) {
				return self::normalize_regions( $regions );
			}
		}

		return self::normalize_regions( self::parse_with_regex( $html ) );
	}

	/**
	 * DOM-based parser.
	 *
	 * @param string $html Raw HTML.
	 * @return array
	 */
	private static function parse_with_dom( $html ) {
		$previous = libxml_use_internal_errors( true );
		$dom      = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$xpath    = new DOMXPath( $dom );
		$sections = $xpath->query( '//section[contains(concat(" ", normalize-space(@class), " "), " tree ")]' );
		$regions  = array();
		$seen     = array();

		if ( ! $sections || 0 === $sections->length ) {
			return array();
		}

		foreach ( $sections as $section ) {
			$oblast = $xpath->query( './h2//a[starts-with(@href, "/region/")]', $section )->item( 0 );
			if ( ! $oblast || ! preg_match( '#^/region/(\d+)-([a-z0-9-]+)$#', $oblast->getAttribute( 'href' ), $match ) ) {
				continue;
			}

			$oblast_id = (int) $match[1];
			if ( ! isset( $seen[ $oblast_id ] ) ) {
				$seen[ $oblast_id ] = true;
				$regions[]          = array(
					'id'     => $oblast_id,
					'slug'   => $match[2],
					'name'   => self::decode_text( $oblast->textContent ),
					'level'  => 'oblast',
					'parent' => 0,
				);
			}

			$current_parent = $oblast_id;

			foreach ( $section->childNodes as $child ) {
				if ( ! ( $child instanceof DOMElement ) ) {
					continue;
				}

				if ( 'h3' === $child->tagName ) {
					$link = $xpath->query( './/a[starts-with(@href, "/region/")]', $child )->item( 0 );
					if ( $link && preg_match( '#^/region/(\d+)-([a-z0-9-]+)$#', $link->getAttribute( 'href' ), $match ) ) {
						$id = (int) $match[1];
						if ( ! isset( $seen[ $id ] ) ) {
							$seen[ $id ] = true;
							$regions[]   = array(
								'id'     => $id,
								'slug'   => $match[2],
								'name'   => self::decode_text( $link->textContent ),
								'level'  => 'raion',
								'parent' => $oblast_id,
							);
						}
						$current_parent = $id;
					}
					continue;
				}

				if ( 'ul' === $child->tagName ) {
					foreach ( $xpath->query( './/a[starts-with(@href, "/region/")]', $child ) as $link ) {
						if ( ! preg_match( '#^/region/(\d+)-([a-z0-9-]+)$#', $link->getAttribute( 'href' ), $match ) ) {
							continue;
						}
						$id = (int) $match[1];
						if ( isset( $seen[ $id ] ) ) {
							continue;
						}
						$seen[ $id ] = true;
						$regions[]   = array(
							'id'     => $id,
							'slug'   => $match[2],
							'name'   => self::decode_text( $link->textContent ),
							'level'  => 'hromada',
							'parent' => $current_parent,
						);
					}
				}
			}
		}

		return $regions;
	}

	/**
	 * Regex parser used if DOM is unavailable.
	 *
	 * @param string $html Raw HTML.
	 * @return array
	 */
	private static function parse_with_regex( $html ) {
		$regions = array();
		$seen    = array();

		if ( ! preg_match_all( '#<section[^>]*class="[^"]*\btree\b[^"]*"[^>]*>(.*?)</section>#is', $html, $sections ) ) {
			return array();
		}

		foreach ( $sections[1] as $section ) {
			if ( ! preg_match( '#<h2[^>]*>.*?<a[^>]+href="/region/(\d+)-([a-z0-9-]+)"[^>]*>(.*?)</a>.*?</h2>#is', $section, $oblast ) ) {
				continue;
			}

			$oblast_id = (int) $oblast[1];
			if ( ! isset( $seen[ $oblast_id ] ) ) {
				$seen[ $oblast_id ] = true;
				$regions[]          = array(
					'id'     => $oblast_id,
					'slug'   => $oblast[2],
					'name'   => self::decode_text( wp_strip_all_tags( $oblast[3] ) ),
					'level'  => 'oblast',
					'parent' => 0,
				);
			}

			$current_parent = $oblast_id;

			if ( preg_match_all( '#<h3[^>]*>.*?<a[^>]+href="/region/(\d+)-([a-z0-9-]+)"[^>]*>(.*?)</a>.*?</h3>\s*(?:<ul[^>]*>(.*?)</ul>)?#is', $section, $raions, PREG_SET_ORDER ) ) {
				foreach ( $raions as $raion ) {
					$id = (int) $raion[1];
					if ( ! isset( $seen[ $id ] ) ) {
						$seen[ $id ] = true;
						$regions[]   = array(
							'id'     => $id,
							'slug'   => $raion[2],
							'name'   => self::decode_text( wp_strip_all_tags( $raion[3] ) ),
							'level'  => 'raion',
							'parent' => $oblast_id,
						);
					}
					$current_parent = $id;

					if ( ! empty( $raion[4] ) && preg_match_all( '#<a[^>]+href="/region/(\d+)-([a-z0-9-]+)"[^>]*>(.*?)</a>#is', $raion[4], $hromadas, PREG_SET_ORDER ) ) {
						foreach ( $hromadas as $hromada ) {
							$hid = (int) $hromada[1];
							if ( isset( $seen[ $hid ] ) ) {
								continue;
							}
							$seen[ $hid ] = true;
							$regions[]    = array(
								'id'     => $hid,
								'slug'   => $hromada[2],
								'name'   => self::decode_text( wp_strip_all_tags( $hromada[3] ) ),
								'level'  => 'hromada',
								'parent' => $current_parent,
							);
						}
					}
				}
			}
		}

		return $regions;
	}

	/**
	 * Normalize and de-duplicate region rows.
	 *
	 * @param array $regions Raw regions.
	 * @return array
	 */
	private static function normalize_regions( $regions ) {
		$normalized = array();
		$seen       = array();

		foreach ( (array) $regions as $region ) {
			if ( ! is_array( $region ) || empty( $region['id'] ) ) {
				continue;
			}

			$id = absint( $region['id'] );
			if ( ! $id || isset( $seen[ $id ] ) ) {
				continue;
			}

			$level = isset( $region['level'] ) ? sanitize_key( $region['level'] ) : 'oblast';
			if ( ! in_array( $level, array( 'oblast', 'raion', 'hromada' ), true ) ) {
				$level = 'oblast';
			}

			$slug = isset( $region['slug'] ) ? sanitize_title( $region['slug'] ) : '';
			if ( '' === $slug ) {
				continue;
			}

			$seen[ $id ] = true;
			$normalized[] = array(
				'id'     => $id,
				'slug'   => $slug,
				'name'   => self::decode_text( isset( $region['name'] ) ? $region['name'] : '' ),
				'level'  => $level,
				'parent' => isset( $region['parent'] ) ? absint( $region['parent'] ) : 0,
			);
		}

		usort(
			$normalized,
			static function ( $a, $b ) {
				return $a['id'] <=> $b['id'];
			}
		);

		return $normalized;
	}

	/**
	 * Load the bundled fallback snapshot.
	 *
	 * @return array
	 */
	private static function get_snapshot() {
		$file = ARA_DIR . 'data/regions.json';
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$json = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $json ) || empty( $json['regions'] ) || ! is_array( $json['regions'] ) ) {
			return array();
		}

		return self::normalize_regions( $json['regions'] );
	}

	/**
	 * Decode a label and remove markup.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private static function decode_text( $text ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = wp_strip_all_tags( $text );
		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Get the transient cache.
	 *
	 * @return array|false
	 */
	private static function cache_get() {
		return is_multisite() ? get_site_transient( self::CACHE_KEY ) : get_transient( self::CACHE_KEY );
	}

	/**
	 * Store the transient cache.
	 *
	 * @param array $regions Regions.
	 * @return void
	 */
	private static function cache_set( $regions ) {
		$hours = absint( ARA_Settings::get( 'cache_ttl', 24 ) );
		$hours = max( 1, min( 168, $hours ) );
		$ttl   = $hours * HOUR_IN_SECONDS;

		if ( is_multisite() ) {
			set_site_transient( self::CACHE_KEY, $regions, $ttl );
		} else {
			set_transient( self::CACHE_KEY, $regions, $ttl );
		}
	}

	/**
	 * Delete the transient cache.
	 *
	 * @return void
	 */
	private static function cache_delete() {
		if ( is_multisite() ) {
			delete_site_transient( self::CACHE_KEY );
		} else {
			delete_transient( self::CACHE_KEY );
		}
	}

	/**
	 * Get persistent data.
	 *
	 * @return array
	 */
	private static function data_get() {
		$data = is_multisite() ? get_site_option( self::DATA_KEY, array() ) : get_option( self::DATA_KEY, array() );

		if ( ! is_array( $data ) ) {
			return array();
		}

		if ( isset( $data['regions'] ) && ! is_array( $data['regions'] ) ) {
			unset( $data['regions'] );
		}

		return $data;
	}

	/**
	 * Store persistent data.
	 *
	 * @param array $data Data.
	 * @return void
	 */
	private static function data_update( $data ) {
		if ( is_multisite() ) {
			update_site_option( self::DATA_KEY, $data );
		} else {
			update_option( self::DATA_KEY, $data, false );
		}
	}

	/**
	 * Delete persistent data.
	 *
	 * @return void
	 */
	private static function data_delete() {
		if ( is_multisite() ) {
			delete_site_option( self::DATA_KEY );
		} else {
			delete_option( self::DATA_KEY );
		}
	}
}
