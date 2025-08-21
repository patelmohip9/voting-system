<?php

/**
 * The admin dashboard functionality for the voting system.
 *
 * @link       https://patelmohip.in
 * @since      1.0.0
 *
 * @package    Voting_System
 * @subpackage Voting_System/admin
 */

class Voting_System_Admin_Dashboard {

	/**
	 * The voting system votes instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Voting_System_Votes    $votes    The votes handler.
	 */
	private $votes;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->votes = new Voting_System_Votes();
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Add admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Post Votes', 'voting-system' ),
			__( 'Post Votes', 'voting-system' ),
			'manage_options',
			'post-votes',
			[ $this, 'display_admin_page' ],
			'dashicons-thumbs-up',
			30
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since    1.0.0
	 * @param    string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		if ( 'toplevel_page_post-votes' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'voting-system-admin',
			plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/voting-system-admin.css',
			[],
			'1.0.0'
		);

		wp_enqueue_script(
			'voting-system-admin',
			plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/voting-system-admin.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);
	}

	/**
	 * Display the admin page.
	 *
	 * @since    1.0.0
	 */
	public function display_admin_page() {
		// Handle sorting
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'post_title';
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';

		// Toggle order for next click
		$next_order = 'ASC' === $order ? 'DESC' : 'ASC';

		// Get posts with votes
		$posts = $this->votes->get_posts_with_votes( $orderby, $order );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Post Votes Statistics', 'voting-system' ); ?></h1>
			
			<div class="voting-stats-summary">
				<?php $this->display_summary_stats( $posts ); ?>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-title">
							<a href="<?php echo esc_url( $this->get_sort_url( 'post_title', $next_order ) ); ?>">
								<?php esc_html_e( 'Post Title', 'voting-system' ); ?>
								<?php $this->display_sort_arrow( 'post_title', $orderby, $order ); ?>
							</a>
						</th>
						<th scope="col" class="manage-column column-upvotes">
							<a href="<?php echo esc_url( $this->get_sort_url( 'upvotes', $next_order ) ); ?>">
								<?php esc_html_e( 'Upvotes', 'voting-system' ); ?>
								<?php $this->display_sort_arrow( 'upvotes', $orderby, $order ); ?>
							</a>
						</th>
						<th scope="col" class="manage-column column-downvotes">
							<a href="<?php echo esc_url( $this->get_sort_url( 'downvotes', $next_order ) ); ?>">
								<?php esc_html_e( 'Downvotes', 'voting-system' ); ?>
								<?php $this->display_sort_arrow( 'downvotes', $orderby, $order ); ?>
							</a>
						</th>
						<th scope="col" class="manage-column column-total">
							<a href="<?php echo esc_url( $this->get_sort_url( 'total_votes', $next_order ) ); ?>">
								<?php esc_html_e( 'Total Votes', 'voting-system' ); ?>
								<?php $this->display_sort_arrow( 'total_votes', $orderby, $order ); ?>
							</a>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $posts ) ) : ?>
						<tr>
							<td colspan="6">
								<?php esc_html_e( 'No posts found.', 'voting-system' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $posts as $post ) : ?>
							<tr>
								<td class="column-title">
									<strong>
										<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
											<?php echo esc_html( $post->post_title ); ?>
										</a>
									</strong>
									<div class="row-actions">
										<span class="view">
											<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank">
												<?php esc_html_e( 'View', 'voting-system' ); ?>
											</a>
										</span>
									</div>
								</td>
								<td class="column-upvotes">
									<span class="upvotes-count"><?php echo esc_html( $post->upvotes ); ?></span>
								</td>
								<td class="column-downvotes">
									<span class="downvotes-count"><?php echo esc_html( $post->downvotes ); ?></span>
								</td>
								<td class="column-total">
									<span class="total-votes"><?php echo esc_html( $post->total_votes ); ?></span>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<div class="voting-system-info">
				<h3><?php esc_html_e( 'How to Use', 'voting-system' ); ?></h3>
				<p><?php esc_html_e( 'This voting system provides REST API endpoints for voting on posts:', 'voting-system' ); ?></p>
				<ul>
					<li><strong>GET /wp-json/wp/v2/posts</strong> - <?php esc_html_e( 'Returns posts with vote data included', 'voting-system' ); ?></li>
					<li><strong>POST /wp-json/voting-system/v1/vote</strong> - <?php esc_html_e( 'Submit a vote (requires post_id and vote_type parameters)', 'voting-system' ); ?></li>
					<li><strong>GET /wp-json/voting-system/v1/votes/{id}</strong> - <?php esc_html_e( 'Get vote counts for a specific post', 'voting-system' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Display summary statistics.
	 *
	 * @since    1.0.0
	 * @param    array $posts Posts data.
	 */
	private function display_summary_stats( $posts ) {
		$total_posts    = count( $posts );
		$total_upvotes  = array_sum( array_column( $posts, 'upvotes' ) );
		$total_downvotes = array_sum( array_column( $posts, 'downvotes' ) );
		$total_votes    = $total_upvotes + $total_downvotes;

		?>
		<div class="voting-stats-cards">
			<div class="stat-card">
				<h3><?php echo esc_html( number_format( $total_posts ) ); ?></h3>
				<p><?php esc_html_e( 'Total Posts', 'voting-system' ); ?></p>
			</div>
			<div class="stat-card upvotes">
				<h3><?php echo esc_html( number_format( $total_upvotes ) ); ?></h3>
				<p><?php esc_html_e( 'Total Upvotes', 'voting-system' ); ?></p>
			</div>
			<div class="stat-card downvotes">
				<h3><?php echo esc_html( number_format( $total_downvotes ) ); ?></h3>
				<p><?php esc_html_e( 'Total Downvotes', 'voting-system' ); ?></p>
			</div>
			<div class="stat-card total">
				<h3><?php echo esc_html( number_format( $total_votes ) ); ?></h3>
				<p><?php esc_html_e( 'Total Votes', 'voting-system' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Get sort URL.
	 *
	 * @since    1.0.0
	 * @param    string $orderby Order by column.
	 * @param    string $order   Order direction.
	 * @return   string Sort URL.
	 */
	private function get_sort_url( $orderby, $order ) {
		return add_query_arg(
			[
				'orderby' => $orderby,
				'order'   => $order,
			],
			admin_url( 'admin.php?page=post-votes' )
		);
	}

	/**
	 * Display sort arrow.
	 *
	 * @since    1.0.0
	 * @param    string $column      Column name.
	 * @param    string $current_orderby Current orderby.
	 * @param    string $current_order   Current order.
	 */
	private function display_sort_arrow( $column, $current_orderby, $current_order ) {
		if ( $column === $current_orderby ) {
			$arrow = 'ASC' === $current_order ? '▲' : '▼';
			echo '<span class="sort-arrow">' . esc_html( $arrow ) . '</span>';
		}
	}
}
