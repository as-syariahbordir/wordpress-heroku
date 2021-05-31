<h2><?php _e('OngkosKirim.id','ongkoskirim-id'); ?></h2>


<?php
//print_r($options);
//include_once __DIR__ . "/ongkoskirim-id-admin-settings-page-tabs.php"; ?>
<?php include_once __DIR__ . "/ongkoskirim-id-admin-settings-page-header.php"; ?>

<table class="form-table">
    <?php $this->generate_settings_html(); ?>
<?php wp_nonce_field("ongkoskirim-id-settings", "ongkoskirim-id-nonce"); ?>
    <?php if (!$this->lib->is_license_active()) : ?>
<tr class="" >
    <th scope="row" class="titledesc">
        <label><?php echo __('Versi OngkosKirim.id',"ongkoskirim-id")?> </label>
    </th>
    <td class="forminp">
        <fieldset>
            <div class="switch">
                <input type="radio" id="version_type_free" name="version_type" value="0" <?php echo $options['version_type'] == '0' ? 'checked="checked"' : ''; ?>  class="switch-input" />
                <label for="version_type_free"  class="switch-label switch-label-off"><?php echo __('Gratis',"ongkoskirim-id"); ?></label>
                <input type="radio" id="version_type_pro" name="version_type" value="1" <?php echo $options['version_type'] == '1' ? 'checked="checked"' : ''; ?>  class="switch-input" />
                <label for="version_type_pro" class="switch-label switch-label-on">PRO</label>
                <span class="switch-selection"></span>
            </div>
        </fieldset>
    </td>
    <td class="legend">
        <legend class=""><span><?php echo __('Apakah Anda menggunakan versi gratis atau berbayar?',"ongkoskirim-id")?></span></legend>
    </td>
</tr>
    <?php endif; ?>

<tr class="toggle_version_type <?php if($options['version_type']=='1') echo "showme"; else echo "hideme"; ?>" >
    <th scope="row" class="titledesc ">
        <label for=""><?php echo __('Lisensi OngkosKirim.id',"ongkoskirim-id")?></label>
    </th>
    <td class="forminp" >
        <fieldset>
                <?php if (!$this->lib->is_license_active()) : ?>
                    <input type="text" id="license_key_input" name="license_key_input" style="max-width:350px; width:100%;" value="<?php echo $options['license_key'] ?>"/>
                    <div class="license-response">
                        <div>
                            <div style="padding-top: 20px; display: none;" class="loadingspinner"><img src="<?php echo dirname(plugin_dir_url( __FILE__ ));?>/images/loading.gif" style="width: 60px"></div>
                            <div class="msg" style="padding-top: 20px; padding-bottom: 20px; display: none" ></div>
                        </div>
                            <button type="button" class="button-primary" id="activate-license">Activate</button>
                    </div>
                <?php else: ?>
                    <input type="hidden" id="license_key_input" name="license_key_input" style="max-width:350px; width:100%;" value="<?php echo $options['license_key'] ?>"/>
            <div class="license-response">

                        <span class="active"><?php echo $options['license_key'] ?></span>
                    <div style="padding-top: 20px; display: none;" class="loadingspinner"><img src="<?php echo dirname(plugin_dir_url( __FILE__ ));?>/images/loading.gif" style="width: 60px"></div>
                    <div class="msg" style="padding-top: 20px; padding-bottom: 20px; display: none" ></div>
                        <button type="button" class="button" id="deactive-license">Deactive License</button>
                    </div>
                <?php endif; ?>

        </fieldset>
    </td>
    <td class="legend">
        <legend class="">
			<span>
				<?php echo __('Kode Lisensi Plugin OngkosKirim.id versi PRO. <br>Dapatkan lisensinya <a target="_blank" href="http://plugin.ongkoskirim.id">di sini</a>',"ongkoskirim-id")?>
			</span>
        </legend>
    </td>
</tr>


    <tr >
        <th scope="row" class="titledesc">
            <label for=""><?php echo __('Pilih Ekspedisi', "ongkoskirim-id");?></label>
        </th>
        <td class="forminp">
            <fieldset>
                <!--<select id="shipping_company" multiple="multiple" class="multiselect chosen_select" name="shipping_company_enabled[]" >-->
                    <?php
                    foreach ($company as $company_id => $company_title) {


                        if( in_array($company_id, $options['shipping_company_enabled'])) {
                            $selected = 'checked="checked"';
                        } else {
                            $selected = '';
                        }
                        $disabled   = $disabledfont = $disabledtext = "";
                        if( !$this->lib->is_license_active() && in_array($company_id, $this->shipping_company_pro) ){
                            $disabled   = "disabled";
                            $disabledfont   = "style='text-decoration:line-through'";
                            $disabledtext   = "<i style='color: red;'>".__('Hanya tersedia di versi PRO',"ongkoskirim-id")."</i>";
                        }
                        ?>
                        <div>
                        <input type="checkbox" name="shipping_company_enabledbox[<?php echo $company_id;?>]" id="shipping_company_enabled_<?php echo $company_id;?>" value="1" <?php echo $selected; ?> <?php echo $disabled; ?> />
                        <label for="shipping_company_enabled_<?php echo $company_id;?>" <?php echo $disabledfont; ?> ><?php echo $company_title;?></label> <?php echo $disabledtext; ?>
                        </div>
                        <?php
                    }
                    ?>
                <!--</select>-->
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Beberapa ekspedisi hanya bisa digunakan di versi PRO.', "ongkoskirim-id"); ?> <br>
                    <?php
                    if( !$this->lib->is_license_active() ){
                    ?>
                    <a href="http://plugin.<?php echo $this->lib->domain; ?>/"><?php echo __('Upgrade sekarang', "ongkoskirim-id"); ?></a>
                    <?php
                    }
                    ?>
                </span></legend>
        </td>
    </tr>

    <tr valign="top"  id="store-first">
        <th scope="row" class="titledesc">
            <label for=""><?php echo __('Lokasi Toko',"ongkoskirim-id")?></label>
        </th>
        <td class="forminp" id="table-storelocation">
            <fieldset style="width:350px">
                    <select id="store_city_id" name="store_city_id" >
                        <option value=""><?php _e('Pilih Kota', "ongkoskirim-id") ?></option>
                        <?php if (!empty($cities_from)) : ?>
                            <?php foreach ($cities_from['city_id'] as $city_id=>$city_title) : ?>
                                <?php
                                if( $city_id == $options['store_city_id']) {
                                    $selected = 'selected="selected"';
                                } else {
                                    $selected = '';
                                }
                                ?>
                                <option value="<?php echo $city_id ?>" <?php echo $selected; ?> ><?php echo $city_title; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Pilih kota asal perhitungan ongkos kirim',"ongkoskirim-id")?></span></legend>
        </td>
    </tr>




<tr class="">
    <th scope="row" class="titledesc">
        <label><?php echo __('Tampilkan berat pengiriman?',"ongkoskirim-id")?></label>
    </th>
    <td class="forminp">
        <fieldset>
            <p class="switch">
                <input type="radio" id="is_show_weight_yes" name="is_show_weight" value="1" <?php echo $options['is_show_weight'] == '1' ? 'checked="checked"' : ''; ?>  class="switch-input"  />
                <label for="is_show_weight_yes"  class="switch-label switch-label-off">
                    <span><?php echo __("Ya","ongkoskirim-id"); ?></span>
                </label>
                <input type="radio" id="is_show_weight_no" name="is_show_weight" value="0" <?php echo $options['is_show_weight'] == '0' ? 'checked="checked"' : ''; ?>  class="switch-input"  />
                <label for="is_show_weight_no"  class="switch-label switch-label-on">
                    <span><?php echo __("Tidak","ongkoskirim-id"); ?></span>
                </label>
                <span class="switch-selection"></span>
            </p>
        </fieldset>
    </td>
    <td class="legend">
        <legend class=""><span><?php echo __('Apakah berat pengiriman akan ditampilkan dihalaman checkout?',"ongkoskirim-id")?></span></legend>
    </td>
</tr>

<tr class="">
    <th scope="row" class="titledesc">
        <label><?php echo __('Aktifkan Kode Unik Untuk Setiap Pembelian?',"ongkoskirim-id")?></label>
    </th>
    <td class="forminp">
        <fieldset>
            <p class="switch">
                <input type="radio" id="is_unique_code_yes" name="is_unique_code" value="1" <?php echo $options['is_unique_code'] == '1' ? 'checked="checked"' : ''; ?> class="switch-input"/>
                <label for="is_unique_code_yes" class="switch-label switch-label-off">
                    <span><?php echo __("Ya","ongkoskirim-id"); ?></span>
                </label>
                <input type="radio" id="is_unique_code_no" name="is_unique_code" value="0" <?php echo $options['is_unique_code'] == '0' ? 'checked="checked"' : ''; ?> class="switch-input" />
                <label for="is_unique_code_no" class="switch-label switch-label-on">
                    <span><?php echo __("Tidak","ongkoskirim-id"); ?></span>
                </label>
                <span class="switch-selection"></span>
            </p>
        </fieldset>
    </td>
    <td class="legend">
        <legend class=""><span><?php echo __('Untuk mempermudah cek mutasi transaksi di rekening bank, Anda bisa mengaktifkan kode unik. <br>Biaya kode unik akan ditambahkan otomatis pada total belanja tiap pembelian.',"ongkoskirim-id")?></span></legend>
    </td>
</tr>

    <tr class="toggle_is_unique_code <?php if($options['is_unique_code']=='1') echo "showme"; else echo "hideme"; ?>" >
        <th scope="row" class="titledesc">
            <label><?php echo __('Berapa Digit Kode Uniknya?',"ongkoskirim-id")?></label>
        </th>
        <td class="forminp">
            <fieldset>
                <select name="unique_code_length" style="width:100px">
                    <?php
                    for($i=1;$i<=5;$i++){
                        ?>
                        <option <?php echo $options['unique_code_length'] == $i ? "selected" : ""; ?> value="<?php echo $i ?>"><?php echo $i ?></option>
                        <?php
                    }
                    ?>
                </select>
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Panjang digit kode unik',"ongkoskirim-id")?></span></legend>
        </td>
    </tr>

    <tr  >
        <th scope="row" class="titledesc">
            <label><?php echo __('Default Berat Pengiriman',"ongkoskirim-id")?></label>
        </th>
        <td class="forminp">
            <fieldset>
                <input type="number" value="<?php echo $options['default_weight'] ?>" name="default_weight" style="width:100px" /> kg
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Jika berat produk tidak diisi, setiap produk akan dihitung menggunakan berat default ini',"ongkoskirim-id")?></span></legend>
        </td>
    </tr>

    <tr class="">
        <th scope="row" class="titledesc">
            <label><?php echo __('Kenakan Biaya Tambahan Ongkos Kirim?',"ongkoskirim-id")?></label>
        </th>
        <td class="forminp">

            <fieldset>
                <div class="switch">
                    <input type="radio" id="is_added_cost_enable_yes" name="is_added_cost_enable" value="1" <?php echo $options['is_added_cost_enable'] == '1' ? 'checked="checked"' : ''; ?> class="switch-input"/>
                    <label for="is_added_cost_enable_yes" class="switch-label switch-label-off"><span><?php echo __("Ya","ongkoskirim-id"); ?></span></label>
                    <input type="radio" id="is_added_cost_enable_no" name="is_added_cost_enable" value="0" <?php echo $options['is_added_cost_enable'] == '0' ? 'checked="checked"' : ''; ?> class="switch-input"/>
                    <label for="is_added_cost_enable_no" class="switch-label switch-label-on"><span><?php echo __("Tidak","ongkoskirim-id"); ?></span></label>
                    <span class="switch-selection"></span>
                </div>
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Biaya tambahan yang dikenakan ke pembeli untuk setiap pengiriman.',"ongkoskirim-id")?></span></legend>
        </td>
    </tr>


    <tr class="toggle_is_added_cost_enable <?php if($options['is_added_cost_enable']=='1') echo "showme"; else echo "hideme"; ?>" >
        <th scope="row" class="titledesc">
            <label><?php echo __('Tambahan biaya ongkos kirim',"ongkoskirim-id")?></label>
        </th>
        <td class="forminp">
            <fieldset>
                <input type="number" value="<?php echo $options['added_cost'] ?>" name="added_cost" style="width:100px" />
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Biaya tambahan yang dikenakan ke pembeli untuk setiap pengiriman.',"ongkoskirim-id")?></span></legend>
        </td>
    </tr>


    <tr class="">
        <th scope="row" class="titledesc">
            <label><?php echo __('Toleransi berat pengiriman',"ongkoskirim-id")?></label>
        </th>
        <td class="forminp">
            <fieldset>
                <input type="number" value="<?php echo $options['weight_tolerance'] ?>" name="weight_tolerance" style="width:100px" /> gr
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Berapa gram toleransi berat pengirimannya? Misalnya toleransi 300 gram, maka jika total belanja adalah 2200 gram, akan dihitung sebagai 2 kg',"ongkoskirim-id")?></span></legend>
        </td>
    </tr>

    <tr class="">
        <th scope="row" class="titledesc">
            <label><?php echo __('Hapus Cache',"ongkoskirim-id")?></label>
        </th>
        <td class="forminp">
            <fieldset>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wc-settings&tab=shipping&section=ongkoskirim-id'), 'ongkoskirim-id-remove-cache', 'ongkoskirim-id-nonce') ?>" class="button oidbtn oiddanger" onclick="return confirm('<?php _e('Apakah Anda yakin untuk menghapus cache nya?', "ongkoskirim-id") ?>')" style=""><?php echo __("Hapus", "ongkoskirim-id"); ?></a>
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Hapus data cache, dan ambil data terbaru dari OngkosKirim.id', "ongkoskirim-id")?></span></legend>
        </td>
    </tr>

    <tr class="">
        <th scope="row" class="titledesc">
            <label><?php echo __('Reset Konfigurasi',"ongkoskirim-id")?></label>
        </th>
        <td class="forminp">
            <fieldset>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wc-settings&tab=shipping&section=ongkoskirim-id'), 'ongkoskirim-id-reset-options', 'ongkoskirim-id-nonce') ?>" class="button oidbtn oiddanger" onclick="return confirm('<?php _e('Apakah Anda yakin mengembalikan konfigurasi ke awal?', "ongkoskirim-id") ?>')" style=""><?php echo __("Reset", "ongkoskirim-id"); ?></a>
            </fieldset>
        </td>
        <td class="legend">
            <legend class=""><span><?php echo __('Kembalikan konfigurasi ke default', "ongkoskirim-id")?></span></legend>
        </td>
    </tr>

</table>

<script>
    jQuery(function($) {
        $('#activate-license').click(function(){
            var license_key = $('#license_key_input').val();
            $('.license-response .msg').show();
            if (license_key == "") {
                $('.license-response .msg').text('<?php _e("Silahkan isi kode lisensi", "ongkoskirim-id") ?>');
                return;
            }
            $('.license-response .loadingspinner').show();
            $('.license-response .msg').text('<?php _e("Lisensi sedang dicek...", "ongkoskirim-id") ?>');
            $(this).prop('disabled',true);

            $.ajax({
                url: "<?php echo admin_url('admin-ajax.php')?>",
                type: "POST",
                data: {
                    action: "ongkoskirim_id_activate_license",
                    license_key: license_key
                },
                context: this,
                success: function(res) {
                    $('.license-response .loadingspinner').hide();
                    if (res == "success") {
                        location.reload(true);
                    } else if (res == "") {
                        $('.license-response .msg').text('<?php _e("Tidak bisa tersambung ke server", "ongkoskirim-id") ?>');
                        $(this).prop('disabled',false);
                    } else {
                        $('.license-response .msg').text(res);
                        $(this).prop('disabled',false);
                    }
                },
                error: function(err) {
                    $('.license-response .loadingspinner').hide();
                    $('.license-response .msg').text('<?php _e("Tidak bisa tersambung ke server", "ongkoskirim-id") ?>');
                    $(this).prop('disabled',false);
                }
            });
        });


        $('#deactive-license').click(function() {
            var license_key = $('#license_key_input').val();
            $('.license-response .msg').show();
            if (license_key == "") {
                $('.license-response .msg').text('<?php _e("Silahkan isi kode lisensi. Jika mengalami kesulitan, silahkan hubungi team ongkoskirim.id", "ongkoskirim-id") ?>');
                return;
            }
            $('.license-response .loadingspinner').show();
            $('.license-response .msg').text('<?php _e("Lisensi sedang di-deaktivasi...", "ongkoskirim-id") ?>');
            $(this).prop('disabled',true);
            $.ajax({
                url: "<?php echo admin_url('admin-ajax.php')?>",
                type: "POST",
                data: {
                    action: "ongkoskirim_id_deactivate_license",
                    license_key: license_key
                },
                context: this,
                success: function(res) {
                    if (res == "success") {
                        location.reload(true);
                    } else if (res == "") {
                        $('.license-response span').removeClass('loading').text('<?php _e("Tidak bisa tersambung ke server", "ongkoskirim-id") ?>');
                        $(this).prop('disabled',false);
                    } else {
                        $('.license-response span').removeClass('loading').text(res);
                        $(this).prop('disabled',false);
                    }
                },
                error: function(err) {
                    $('.license-response span').removeClass('loading').text('<?php _e("Tidak bisa tersambung ke server", "ongkoskirim-id") ?>');
                    $(this).prop('disabled',false);
                }
            });
        });
    });
</script>