<?php

namespace Megumi\WP;

class AdvancedSearch
{
	public function __construct( $post_type, $tags_term, $taxonomies )
	{
		$this->post_type = $post_type;
		$this->tags_term = $tags_term;
		$this->taxonomies = $taxonomies;
	}

	public function render()
	{
		$post_type = $this->post_type;
		$tags_term = $this->tags_term;
		$taxonomies = $this->taxonomies;

		$tax_query = array();
		$tax_query['relation'] = 'AND';

		foreach ( $taxonomies as $query => $tax ) {
			if ( ! empty( $_GET[ $query ] ) ) {
				if ( is_array( $_GET[ $query ] ) ) {
					$terms = $_GET[ $query ];
				} else {
					$terms = array( $_GET[ $query ] );
				}
				$tax_query[] = array(
					'taxonomy' => $tax,
					'field' => 'id',
					'terms' => $terms,
				);
			}
		}

		if ( $tags_term && ! empty( $_GET['tags'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $tags_term,
				'field' => 'id',
				'terms' => $_GET['tags'],
			);
		}

		if ( ! empty( $_GET['keyword'] ) ) {
			$keyword = $_GET['keyword'];
		} else {
			$keyword = '';
		}

		$args = array(
			'post_type' => $post_type,
			'post_status' => 'publish',
			'nopaging' => true,
			'posts_per_page' => -1,
			'tax_query' => $tax_query,
			's' => $keyword,
		);

		$posts = array();
		foreach ( get_posts( $args ) as $post ) {
			if ( has_post_thumbnail( $post->ID ) ) {
				$post_thumbnail = get_the_post_thumbnail( $post->ID, 'thumbnail' );
			} else {
				$post_thumbnail = '<img src="/wp-content/themes/yoga-gene.com/images/no-image-pc.gif" alt="No image" width="264" height="178">';
			}
			$posts[] = array(
				'post_title' => $post->post_title,
				'post_permalink' => get_the_permalink( $post->ID ),
				'post_thumbnail' => $post_thumbnail,
			);
		}

		$tax_selectors = array();
		foreach ( $taxonomies as $query => $taxonomy ) {
			$label = get_taxonomy( $taxonomy )->label;
			$select = sprintf( '<select name="%s">', esc_attr( $query ) );
			$select .= sprintf( '<option value="">%s</option>', esc_attr( $label . 'で絞り込む' ) );
			foreach ( get_terms( $taxonomy ) as $term ) {
				if ( ! empty( $_GET[ $query ] ) && $term->term_id === $_GET[ $query ] ) {
					$option = '<option value="%s" selected>%s</option>';
				} else {
					$option = '<option value="%s">%s</option>';
				}
				$select .= sprintf( $option, esc_attr( $term->term_id ), esc_attr( $term->name ) );
			}
			$select .= '</select>';
			$tax_selectors[] = $select;
		}

		$tags = array();
		if ( $tags_term ) {
			foreach ( get_terms( $tags_term ) as $term ) {
				$tags[] = array(
					'ID' => $term->term_id,
					'name' => $term->name,
					'checked' => ( ! empty( $_GET['tags'] ) && in_array( $term->term_id, $_GET['tags'] ) )? true: false,
				);
			}
		}

		$template_param = array(
			'post_type_label' => get_post_type_object( $post_type )->label,
			'tax_selectors' => $tax_selectors,
			'keyword' => '<input type="search" name="keyword" placeholder="フリーキーワードで探す" value="'.esc_attr( $keyword ).'">',
			'tags' => $tags,
			'posts' => $posts,
			'posts_count' => count( $posts ),
		);

		$mustache = new Mustache_Engine();
		$template = file_get_contents( dirname( __FILE__ ) . '/views/' . $post_type . '.mustache' );
		return $mustache->render( $template, $template_param );
	}
}
