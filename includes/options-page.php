<div class="wrap">
	<h1>Woocommerce Vietnam Shipping</h1>

	<form method="post" action="options.php" novalidate="novalidate">
	<?php
	settings_fields( $this->_optionGroup );
	$flra_options = wp_parse_args(get_option($this->_optionName),$this->_defaultOptions);
    global $tinh_thanhpho;
	?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="activeplugin"><?php _e('Ẩn mục phường/xã','devvn')?></label></th>
					<td>
						<label><input type="checkbox" name="<?php echo $this->_optionName?>[active_village]" <?php checked('1',$flra_options['active_village'])?> value="1" /> <?php _e('Ẩn mục phường/xã','devvn')?></label>
					</td>
				</tr>
                <tr>
                    <th scope="row"><label for="required_village"><?php _e('KHÔNG bắt buộc nhập phường/xã','devvn')?></label></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo $this->_optionName?>[required_village]" <?php checked('1',$flra_options['required_village'])?> value="1" /> <?php _e('Không bắt buộc','devvn')?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="to_vnd"><?php _e('Chuyển ₫ sang VNĐ','devvn')?></label></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo $this->_optionName?>[to_vnd]" <?php checked('1',$flra_options['to_vnd'])?> value="1" id="to_vnd"/> <?php _e('Cho phép chuyển sang VNĐ','devvn')?></label><br>
                        <small>Xem thêm <a href="https://levantoan.com/thay-doi-ky-hieu-tien-te-dong-viet-nam-trong-woocommerce-d-sang-vnd/" target="_blank"> cách thiết lập đơn vị tiền tệ ₫ (Việt Nam đồng)</a></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="remove_methob_title"><?php _e('Loại bỏ tiêu đề vận chuyển','devvn')?></label></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo $this->_optionName?>[remove_methob_title]" <?php checked('1',$flra_options['remove_methob_title'])?> value="1" id="remove_methob_title"/> <?php _e('Loại bỏ hoàn toàn tiêu đề của phương thức vận chuyển','devvn')?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="freeship_remove_other_methob"><?php _e('Ẩn phương thức khi có free-shipping','devvn')?></label></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo $this->_optionName?>[freeship_remove_other_methob]" <?php checked('1',$flra_options['freeship_remove_other_methob'])?> value="1" id="freeship_remove_other_methob"/> <?php _e('Ẩn tất cả những phương thức vận chuyển khác khi có miễn phí vận chuyển','devvn')?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="khoiluong_quydoi"><?php _e('Số quy đổi','devvn')?></label></th>
                    <td>
                        <input type="number" min="0" name="<?php echo $this->_optionName?>[khoiluong_quydoi]" value="<?php echo $flra_options['khoiluong_quydoi'];?>" id="khoiluong_quydoi"/> <br>
                        <small><?php _e('Thương số quy đổi. Mặc định theo Viettel Post là 6000','devvn')?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="vnd_usd_rate"><?php _e('VNĐ quy đổi sang tiền','devvn')?></label></th>
                    <td>
                        <select name="<?php echo $this->_optionName?>[vnd2usd_currency]" id="vnd2usd_currency">
                            <?php
                            $paypal_supported_currencies = array(
                                'AUD',
                                'BRL',
                                'CAD',
                                'MXN',
                                'NZD',
                                'HKD',
                                'SGD',
                                'USD',
                                'EUR',
                                'JPY',
                                'TRY',
                                'NOK',
                                'CZK',
                                'DKK',
                                'HUF',
                                'ILS',
                                'MYR',
                                'PHP',
                                'PLN',
                                'SEK',
                                'CHF',
                                'TWD',
                                'THB',
                                'GBP',
                                'RMB',
                                'RUB'
                            );
                            foreach ( $paypal_supported_currencies as $currency ) {
                                if ( strtoupper( $currency ) == $flra_options['vnd2usd_currency'] ) {
                                    printf( '<option selected="selected" value="%1$s">%1$s</option>', $currency );
                                } else {
                                    printf( '<option value="%1$s">%1$s</option>', $currency );
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="vnd_usd_rate"><?php _e('Số quy đổi','devvn')?></label></th>
                    <td>
                        <input type="number" min="0" name="<?php echo $this->_optionName?>[vnd_usd_rate]" value="<?php echo $flra_options['vnd_usd_rate'];?>" id="vnd_usd_rate"/> <br>
                        <small><?php _e('Tỷ giá quy đổi từ VNĐ','devvn')?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="alepay_support"><?php _e('Alepay','woo-vietnam-shipping')?></label></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo $this->_optionName?>[enable_firstname]" <?php checked('1',$flra_options['enable_firstname'])?> value="1" /> <?php _e('Hiển thị trường First Name','woo-vietnam-shipping')?></label>
                        <br><small>Để thanh toán qua Alepay bắt buộc phải có first_name.</small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo $this->_optionName?>[enable_country]" <?php checked('1',$flra_options['enable_country'])?> value="1" /> <?php _e('Hiển thị trường Quốc Gia','woo-vietnam-shipping')?></label>
                        <br><small>Để thanh toán qua Alepay bắt buộc phải có country.</small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo $this->_optionName?>[enable_postcode]" <?php checked('1',$flra_options['enable_postcode'])?> value="1" /> <?php _e('Hiện trường Postcode','woo-vietnam-shipping')?></label>
                        <br><small>Nếu sử dụng kiểu thanh toán "Tokenization" của Alepay thì bắt buộc cần Postcode.</small>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="active_filter_order"><?php _e('Bộ lọc','woo-vietnam-shipping')?></label></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo $this->_optionName?>[active_filter_order]" <?php checked('1',$flra_options['active_filter_order'])?> value="1" /> <?php _e('Kích hoạt bộ lọc','woo-vietnam-shipping')?></label>
                        <br><small>Kích hoạt bộ lọc theo tỉnh thành và ngày tháng trong trang danh sách order</small>
                    </td>
                </tr>
                <?php do_settings_fields($this->_optionGroup, 'default'); ?>
			</tbody>
		</table>
		<?php do_settings_sections($this->_optionGroup, 'default'); ?>
		<?php submit_button();?>
	</form>
</div>