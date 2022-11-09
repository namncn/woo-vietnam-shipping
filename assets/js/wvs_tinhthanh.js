(function($) {
	$(document).ready(function() {
		var $defaultSetting = {
			formatNoMatches: vncheckout_array.formatNoMatches,
		};
		var loading_billing = loading_shipping = false;
		var billing_city_field = $('#billing_city_field');
		var billing_address_2_field = $('#billing_address_2_field');
		var shipping_city_field = $('#shipping_city_field');
		var shipping_address_2_field = $('#shipping_address_2_field');
		//billing
		$('#billing_state').select2($defaultSetting);
		$('#billing_city').select2($defaultSetting);
		$('#billing_address_2').select2($defaultSetting);

		$('body #billing_state').on('select2:select select2-selecting', function(e) {
			$("#billing_city option").val('');
			var matp = e.val;
			if (!matp) matp = $("#billing_state option:selected").val();
			if (matp && !loading_billing) {
				loading_billing = true;
				$.ajax({
					type: "post",
					dataType: "json",
					url: vncheckout_array.get_address,
					data: {
						action: "load_diagioihanhchinh",
						matp: matp
					},
					context: this,
					beforeSend: function() {
						billing_city_field.addClass('wvs_loading');
						billing_address_2_field.addClass('wvs_loading');
					},
					success: function(response) {
						loading_billing = false;
						$("#billing_city,#billing_address_2").html('').select2();
						if (response.success) {
							var listQH = response.data;
							var newState = new Option('', '');
							$("#billing_city").append(newState);
							$.each(listQH, function(index, value) {
								var newState = new Option(value.name, value.maqh);
								$("#billing_city").append(newState);
							});
						}
						billing_city_field.removeClass('wvs_loading');
						billing_address_2_field.removeClass('wvs_loading');
					}
				});
			}
		});
		if ($('#billing_address_2').length > 0) {
			$('#billing_city').on('select2:select select2-selecting', function(e) {
				var maqh = e.val;
				if (!maqh) maqh = $("#billing_city option:selected").val();
				if (maqh) {
					$.ajax({
						type: "post",
						dataType: "json",
						url: vncheckout_array.get_address,
						data: {
							action: "load_diagioihanhchinh",
							maqh: maqh
						},
						context: this,
						beforeSend: function() {
							billing_address_2_field.addClass('wvs_loading');
						},
						success: function(response) {
							$("#billing_address_2").html('').select2($defaultSetting);
							if (response.success) {
								var listQH = response.data;
								var newState = new Option('', '');
								$("#billing_address_2").append(newState);
								$.each(listQH, function(index, value) {
									var newState = new Option(value.name, value.xaid);
									$("#billing_address_2").append(newState);
								});
							}
							billing_address_2_field.removeClass('wvs_loading');
						}
					});
				}
			});
		}
		//shipping
		$('#shipping_state').select2($defaultSetting);
		$('#shipping_city').select2($defaultSetting);
		$('#shipping_address_2').select2($defaultSetting);

		$('body #shipping_state').on('select2:select select2-selecting', function(e) {
			$("#shipping_city option").val('');
			var matp = e.val;
			if (!matp) matp = $("#shipping_state option:selected").val();
			if (matp && !loading_shipping) {
				loading_shipping = true;
				$.ajax({
					type: "post",
					dataType: "json",
					url: vncheckout_array.get_address,
					data: {
						action: "load_diagioihanhchinh",
						matp: matp
					},
					context: this,
					beforeSend: function() {
						shipping_city_field.addClass('wvs_loading');
						shipping_address_2_field.addClass('wvs_loading');
					},
					success: function(response) {
						loading_shipping = false;
						$("#shipping_city,#shipping_address_2").html('').select2();
						if (response.success) {
							var listQH = response.data;
							var newState = new Option('', '');
							$("#shipping_city").append(newState);
							$.each(listQH, function(index, value) {
								var newState = new Option(value.name, value.maqh);
								$("#shipping_city").append(newState);
							});
						}
						shipping_city_field.removeClass('wvs_loading');
						shipping_address_2_field.removeClass('wvs_loading');
					}
				});
			}
		});
		if ($('#shipping_address_2').length > 0) {
			$('#shipping_city').on('select2:select select2-selecting', function(e) {
				var maqh = e.val;
				if (!maqh) maqh = $("#shipping_city option:selected").val();
				if (maqh) {
					$.ajax({
						type: "post",
						dataType: "json",
						url: vncheckout_array.get_address,
						data: {
							action: "load_diagioihanhchinh",
							maqh: maqh
						},
						context: this,
						beforeSend: function() {
							shipping_address_2_field.addClass('wvs_loading');
						},
						success: function(response) {
							$("#shipping_address_2").html('').select2($defaultSetting);
							if (response.success) {
								var listQH = response.data;
								var newState = new Option('', '');
								$("#shipping_address_2").append(newState);
								$.each(listQH, function(index, value) {
									var newState = new Option(value.name, value.xaid);
									$("#shipping_address_2").append(newState);
								});
							}
							shipping_address_2_field.removeClass('wvs_loading');
						}
					});
				}
			});
		}
		if ($('#calc_shipping_city_field').length > 0) {
			$(document.body).bind('country_to_state_changed updated_wc_div country_to_state_changing', function() {
				var district_field = $('#calc_shipping_city_field #calc_shipping_city');
				var default_val = district_field.val();
				var state_default = $('#calc_shipping_state').val();

				district_field.select2();
				var loadingText = 'Loading...';
				$('#calc_shipping_city_field .select2-selection__placeholder').html(loadingText);

				var loading_cacl = false;
				var wvs_district_by_state = function(state_id) {

					$('#calc_shipping_city_field .select2-selection__placeholder').html(loadingText);

					if (state_id && !loading_cacl) {
						$.ajax({
							type: "post",
							dataType: "json",
							url: vncheckout_array.get_address,
							data: {
								action: "load_diagioihanhchinh",
								matp: state_id
							},
							context: this,
							beforeSend: function() {
								loading_cacl = true;
							},
							success: function(response) {
								loading_cacl = false;
								if (response.success) {
									var listQH = response.data;
									var data = [];
									$.each(listQH, function(index, value) {
										data.push({
											id: value.maqh,
											text: value.name
										});
									});
									district_field.html('').select2({
										placeholder: 'Chọn quận/huyện',
										data: data,
									});
								}
							}
						});
					}
				};
				var wvs_city_to_district_select2 = function() {
					$('body select.state_select:visible').each(function() {
						wvs_district_by_state($(this).val());
						$(this, 'body').on('change select2:select', function() {
							wvs_district_by_state($(this).val());
						});
					});
				};
				wvs_city_to_district_select2();
			});
		}
	});
})(jQuery);