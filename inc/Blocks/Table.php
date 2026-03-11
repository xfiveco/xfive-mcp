<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Table block (core/table).
 *
 * The table block stores its content as structured attributes (head, body, foot),
 * each containing rows and cells, rather than using inner blocks.
 */
class Table extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/table';
	}

	/**
	 * Generate HTML for the table block.
	 *
	 * Builds a <figure> wrapper containing a <table> from the structured
	 * head/body/foot attributes. Each section is an array of rows, each row
	 * containing an array of cells with 'content' and optional 'tag' (td/th).
	 *
	 * Expected attrs shape:
	 *   head: [ { cells: [ { content: string, tag: 'th'|'td' } ] } ]
	 *   body: [ { cells: [ { content: string, tag: 'th'|'td' } ] } ]
	 *   foot: [ { cells: [ { content: string, tag: 'th'|'td' } ] } ]
	 *   caption: string (optional)
	 *   hasFixedLayout: bool (optional)
	 *
	 * @param string $content     Unused (table has no free-text content).
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Unused (table has no inner blocks).
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$has_fixed_layout = ! empty( $attrs['hasFixedLayout'] );
		$caption          = isset( $attrs['caption'] ) ? wp_kses_post( $attrs['caption'] ) : '';

		// Table has __experimentalSkipSerialization for color and border.
		$css_classes = array_merge(
			array( 'wp-block-table' ),
			$this->build_classes( $attrs, array( 'textColor', 'backgroundColor', 'gradient', 'fontSize', 'fontFamily', 'borderColor' ) )
		);
		if ( $has_fixed_layout ) {
			$css_classes[] = 'has-fixed-layout';
		}

		$table_html  = '';
		$table_html .= $this->render_section( $attrs['head'] ?? array(), 'thead', 'th' );
		$table_html .= $this->render_section( $attrs['body'] ?? array(), 'tbody', 'td' );
		$table_html .= $this->render_section( $attrs['foot'] ?? array(), 'tfoot', 'td' );

		$table_class = $has_fixed_layout ? ' class="has-fixed-layout"' : '';

		$style_str    = $this->build_styles( $attrs, array( 'border', 'color' ) );
		$class_attr   = $this->build_class_attr( $css_classes );
		$style_attr   = $this->build_style_attr( $style_str );

		$figure_html  = '<figure' . $class_attr . $style_attr . '>';
		$figure_html .= '<table' . $table_class . '>';
		$figure_html .= $table_html;
		$figure_html .= '</table>';

		if ( ! empty( $caption ) ) {
			$figure_html .= '<figcaption class="wp-element-caption">' . $caption . '</figcaption>';
		}

		$figure_html .= '</figure>';

		return $figure_html;
	}

	/**
	 * Render a table section (thead, tbody, or tfoot).
	 *
	 * @param array  $rows        Array of row data.
	 * @param string $section_tag HTML section tag (thead, tbody, tfoot).
	 * @param string $default_tag Default cell tag (th or td).
	 * @return string Rendered section HTML.
	 */
	private function render_section( array $rows, string $section_tag, string $default_tag ): string {
		if ( empty( $rows ) ) {
			return '';
		}

		$html = '<' . $section_tag . '>';

		foreach ( $rows as $row ) {
			$html .= '<tr>';

			foreach ( $row['cells'] ?? array() as $cell ) {
				$tag          = in_array( $cell['tag'] ?? '', array( 'th', 'td' ), true ) ? $cell['tag'] : $default_tag;
				$cell_content = isset( $cell['content'] ) ? wp_kses_post( $cell['content'] ) : '';
				$html        .= '<' . $tag . '>' . $cell_content . '</' . $tag . '>';
			}

			$html .= '</tr>';
		}

		$html .= '</' . $section_tag . '>';

		return $html;
	}
}
