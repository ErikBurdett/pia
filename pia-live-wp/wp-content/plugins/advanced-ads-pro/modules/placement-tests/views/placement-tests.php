<?php
/**
 * Displays the placement test table.
 *
 * @package AdvancedAds\Pro
 *
 * @var array $placement_tests The array with saved tests.
 */

if ( count( $placement_tests ) ) : ?>
	<h2><?php esc_html_e( 'Placement tests', 'advanced-ads-pro' ); ?></h2>
	<div id="placement-tests">
		<table class="form-table advads-placement-tests-table striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Author', 'advanced-ads-pro' ); ?></th>
					<th><?php esc_html_e( 'Expiry date', 'advanced-ads-pro' ); ?></th>
					<th><?php esc_html_e( 'Placements', 'advanced-ads-pro' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $placement_tests as $slug => $placement_test ) :
				$placement_names = $this->get_placement_names( $placement_test );
				$is_empty_test   = count( $placement_names ) < 2;
				$p_user_login    = '';
				if ( isset( $placement_test['user_id'] ) ) {
					$user = get_user_by( 'ID', $placement_test['user_id'] );
					if ( $user ) {
						$p_user_login = $user->user_login;
					}
				}
				?>
			<tr>
				<td>
					<?php echo esc_html( $p_user_login ); ?>
				</td>
				<td>
					<?php
					if ( ! $is_empty_test ) {
						$expiry_date = $placement_test['expiry_date'] ?? false;
						$this->output_expiry_date_form( $slug, $expiry_date );
					}
					?>
				</td>
				<td>
					<?php
					if ( ! $is_empty_test ) {
						echo wp_kses_post( implode( _x( ' vs ', 'placement tests', 'advanced-ads-pro' ), $placement_names ) );
					} else {
						?>
						<span class="advads-notice-inline advads-error"><?php echo esc_html_x( 'empty', 'placement tests', 'advanced-ads-pro' ); ?> </span>
						<?php
					}
					?>
				</td>
				<td>
					<label><input type="checkbox" name="advads[placement_tests][<?php echo esc_attr( $slug ); ?>][delete]" value="1" /> <?php echo esc_html_x( 'delete', 'checkbox to remove placement test', 'advanced-ads-pro' ); ?></label>
				</td>
			<tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<input type="submit" class="button button-primary" id="update-placement-tests" value="<?php esc_html_e( 'Save Tests', 'advanced-ads-pro' ); ?>"/>
		</p>
	</div>
<?php endif; ?>
