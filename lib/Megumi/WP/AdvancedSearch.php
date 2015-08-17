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

	public function get_posts()
	{
		$queries = $this->get_queries();

		$tax_query = array();
		$tax_query['relation'] = 'AND';

		foreach ( $queries->taxonomies as $taxonomy => $term ) {
			$tax_query[] = array(
				'taxonomy' => $this->taxonomies[ $taxonomy ],
				'field' => 'id',
				'terms' => $term,
			);
		}

		if ( $this->tags_term && $queries->tags ) {
			$tax_query[] = array(
				'taxonomy' => $this->tags_term,
				'field' => 'id',
				'terms' => $queries->tags,
			);
		}

		$args = array(
			'post_type' => $this->post_type,
			'post_status' => 'publish',
			'nopaging' => true,
			'posts_per_page' => -1,
			'tax_query' => $tax_query,
			's' => $queries->keyword,
		);

		$posts = array();
		return get_posts( $args );
	}

	public function get_selects()
	{
		$queries = $this->get_queries();

		$tax_selectors = array();
		foreach ( $this->taxonomies as $query => $taxonomy ) {
			$label = get_taxonomy( $taxonomy )->label;
			$select = sprintf( '<select name="%s">', esc_attr( $query ) );
			$select .= sprintf( '<option value="">%s</option>', esc_attr( $label . 'で絞り込む' ) );
			foreach ( get_terms( $taxonomy, array( 'orderby' => 'id', 'order' => 'ASC' ) ) as $term ) {
				if ( ! empty( $queries->taxonomies[ $query ] ) && $term->term_id === $queries->taxonomies[ $query ] ) {
					$option = '<option value="%s" selected>%s</option>';
				} else {
					$option = '<option value="%s">%s</option>';
				}
				$select .= sprintf( $option, esc_attr( $term->term_id ), esc_attr( $term->name ) );
			}
			$select .= '</select>';
			$tax_selectors[] = $select;
		}

		return $tax_selectors;
	}

	public function get_tags()
	{
		$queries = $this->get_queries();

		$tags = array();
		if ( $this->tags_term ) {
			foreach ( get_terms( $this->tags_term ) as $term ) {
				$tags[] = sprintf(
					'<label><input type="checkbox" name="t" value="%1$s" %3$s> %2$s</label>',
					esc_attr( $term->term_id ),
					esc_html( $term->name ),
					( ! empty( $_GET['tags'] ) && in_array( $term->term_id, $_GET['tags'] ) ) ? 'checked': ''
				);
			}
		}

		return $tags;
	}

	public function get_queries()
	{
		if ( ! empty( $_GET['q'] ) ) {
			$keyword = $_GET['q'];
		} else {
			$keyword = '';
		}

		if ( $this->tags_term && ! empty( $_GET['t'] ) ) {
			$tags = $_GET['t'];
		} else {
			$tags = array();
		}

		$taxonomies = array();
		foreach ( $this->taxonomies as $query => $tax ) {
			if ( ! empty( $_GET[ $query ] ) ) {
				if ( is_array( $_GET[ $query ] ) ) {
					$terms = array( $_GET[ $query ] );
				} else {
					$terms = $_GET[ $query ];
				}
				$taxonomies[$query] = $terms;
			}
		}

		$query = new \stdClass();
		$query->keyword = $keyword;
		$query->tags = $tags;
		$query->taxonomies = $taxonomies;

		return $query;
	}
}
