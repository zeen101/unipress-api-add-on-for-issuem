<?php
/**
 * Registers UniPress API Add-on for IssueM class
 *
 * @package UniPress API Add-on for IssueM
 * @since 1.0.0
 */

/**
 * This class registers the main issuem functionality
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'UniPress_API_for_IssueM' ) ) {
	
	class UniPress_API_for_IssueM {
		
		/**
		 * Class constructor, puts things in motion
		 *
		 * @since 1.0.0
		 */
		function __construct() {
			
			$settings = get_issuem_settings();
			
			add_filter( 'process-unipress-api-request-get-issues', array( $this, 'get_issues' ) );
			add_filter( 'process-unipress-api-request-get-current-issue', array( $this, 'get_current_issue' ) );
			add_filter( 'process-unipress-api-request-get-issue', array( $this, 'get_issue' ) );
			add_filter( 'unipress_api_post', array( $this, 'unipress_api_post' ) );
			
			if ( !empty( $settings['issuem_author_name'] ) ) {
				add_filter( 'unipress_api_get_content_list_post_author', array( $this, 'post_author' ), 10, 2 );
				add_filter( 'unipress_api_get_article_post_author', array( $this, 'post_author' ), 10, 2 );
				add_filter( 'unipress_api_get_content_list_author_meta', array( $this, 'author_meta' ), 10, 2 );
				add_filter( 'unipress_api_get_article_author_meta', array( $this, 'author_meta' ), 10, 2 );
			}

		}
		
		function post_author( $post_author, $post ) {
			if ( !empty( $post->ID ) ) {
				if ( get_post_meta( $post->ID, '_issuem_author_name', true ) ) {
					$post_author = 0;
				}
			}
			return $post_author;
		}
		
		function author_meta( $author_meta, $post ) {
			if ( !empty( $post->ID ) ) {
				if ( $author_name = get_post_meta( $post->ID, '_issuem_author_name', true ) ) {
					$names = explode( ' ', $author_name );
					$first = array_shift( $names );
					$last = array_pop( $names );
					$author_meta = new stdClass();
					$author_meta->user_login 	 = $author_name;
					$author_meta->user_nicename  = $author_name;
					$author_meta->display_name 	 = $author_name;
					$author_meta->nickname 		 = $author_name;
					$author_meta->first_name 	 = $first;
					$author_meta->last_name 	 = $last;
					$author_meta->user_firstname = $first;
					$author_meta->user_lastname  = $last;
					$author_meta->description 	 = $author_name;
				}
			}
			return $author_meta;
		}
		
		function get_issues( $response ) {
			$args['issues_per_page'] 	= !empty( $_REQUEST['issues_per_page'] ) 	? intval( $_REQUEST['issues_per_page'] ) : 0;
			$args['offset'] 			= !empty( $_REQUEST['page'] ) 			 	? $args['issues_per_page'] * intval( $_REQUEST['page'] ) : 0;
			$args['orderby'] 			= !empty( $_REQUEST['orderby'] ) 		 	? sanitize_text_field( $_REQUEST['orderby'] ) : 'issue_order'; //name, term_id
			$args['order'] 				= !empty( $_REQUEST['order'] ) 				? sanitize_text_field( $_REQUEST['order'] ) : 'DESC'; //ASC
			
			if ( ! in_array( $args['order'], array( 'ASC', 'asc', 'DESC', 'desc' ) ) ) {
				$args['order'] = 'DESC';
			}
	
			$issues = array();
			$count = 0;
			
			$issuem_issues = get_terms( 'issuem_issue', array( 'hide_empty' => false ) );

			foreach ( $issuem_issues as $issue ) {
					
				$issue_meta = get_option( 'issuem_issue_' . $issue->term_id . '_meta' );
				
				// If issue is not a Draft, add it to the archive array;
				if ( !empty( $issue_meta ) && !empty( $issue_meta['issue_status'] ) && ( 'Live' === $issue_meta['issue_status'] || 'PDF Archive' === $issue_meta['issue_status'] ) ) {
					
					switch( $args['orderby'] ) {
						
						case "issue_order":
							if ( !empty( $issue_meta['issue_order'] ) )
								$issues[ $issue_meta['issue_order'] ] = $issue;
							else
								$issues[ '-' . ++$count ] = $issue;
								
							break;
							
						case "name":
							$issues[ $issue_meta['name'] ] = $issue;
							break;
						
						case "term_id":
							$issues[ $issue->term_id ] = $issue;
							break;
						
					}
						 
				}
				
			}
			
			krsort( $issues );
			
			if ( !empty( $args['issues_per_page'] ) )
				$issues = array_slice( $issues, $args['offset'], $args['issues_per_page'] );
			
			if ( !empty( $issues ) ) {
				foreach ( $issues as $issue ) {
					$meta = get_option( 'issuem_issue_' . $issue->term_id . '_meta' );
					if ( !empty( $meta['cover_image'] ) ) {
						$attachment = wp_get_attachment_image_src( $meta['cover_image'], 'issuem-cover-image' );
						if ( !empty( $attachment[0] ) ) {
							$issue->cover_image = $attachment[0];
						} else {
							$issue->cover_image = false;
						}
					} else {
						$issue->cover_image = false;
					}
					
					if ( !empty( $meta['pdf_version'] ) || !empty( $meta['external_pdf_link'] ) ) {
						$pdf_url = empty( $meta['external_pdf_link'] ) ? wp_get_attachment_url( $meta['pdf_version'] ) : $meta['external_pdf_link'];
						if ( !empty( $pdf_url ) ) {
							$issue->pdf = $pdf_url;
						} else {
							$issue->pdf = false;
						}
					} else {
						$issue->pdf = false;
					}
					$body[] = $issue;
				}
				$response = array(
					'http_code' => 200,
					'body' 		=> $body,
				);
			} else {
				$response = array(
					'http_code' => 204,
					'body' 		=> __( 'No Issue Found.', 'unipress-api-issuem' ),
				);
			}

			return $response;
		}
		
		function get_current_issue( $response ) {
			$args['orderby'] = !empty( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'issue_order'; //name, term_id

			$_REQUEST['issue-id'] = get_newest_issuem_issue_id( $args['orderby'] );
			return $this->get_issue( $response );
		}
		
		function get_issue( $response ) {
			try {
				if ( empty( $_REQUEST['issue-id'] ) ) {
					throw new Exception( __( 'Missing Issue ID.', 'unipress-api-issuem' ), 400 );
				} else if ( !is_numeric( $_REQUEST['issue-id'] ) ) {
					throw new Exception( __( 'Invalid Issue ID Format.', 'unipress-api-issuem' ), 400 );
				} else {
					$issue_id = $_REQUEST['issue-id'];
				}
				
				$issue = get_term( $issue_id, 'issuem_issue' );
				if ( !empty( $issue ) ) {
					$meta = get_option( 'issuem_issue_' . $issue->term_id . '_meta' );
					if ( !empty( $meta['cover_image'] ) ) {
						$attachment = wp_get_attachment_image_src( $meta['cover_image'], 'issuem-cover-image' );
						if ( !empty( $attachment[0] ) ) {
							$issue->cover_image = $attachment[0];
						} else {
							$issue->cover_image = false;
						}
					} else {
						$issue->cover_image = false;
					}
					$response = array(
						'http_code' => 200,
						'body' 		=> $issue,
					);
				} else {
					$response = array(
						'http_code' => 204,
						'body' 		=> __( 'No Issue Found.', 'unipress-api-issuem' ),
					);
				}
	
				return $response;
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}

		}
		
		function unipress_api_post( $post ) {
			$teaser = get_post_meta( $post->ID, '_teaser_text', true );
			$post->teaser = $teaser;
			return $post;
		}

	}

}
