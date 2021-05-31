(function ($) {
  'use strict'

  var debug = 1

  var ref_city, ref_state
  var ref_district = []

  $(window).load(function () {
    OngkosKirimId_Init()
  })

  // actions goes here
  function OngkosKirimId_Init () {
    OngkosKirimId_LoadCity()

    OngkosKirimId_CreateInputCityText('billing')
    OngkosKirimId_CountryOnchange('billing')
    OngkosKirimId_StateOnChange('billing')
    OngkosKirimId_CityOnChange('billing')

    OngkosKirimId_CreateInputCityText('shipping')
    OngkosKirimId_CountryOnchange('shipping')
    OngkosKirimId_StateOnChange('shipping')
    OngkosKirimId_CityOnChange('shipping')

    OngkosKirimId_LoadExistingCustomer()
    OngkosKirimId_ReorderFieldsDisplay()

    $(document.body).bind('country_to_state_changing', function () { OngkosKirimId_ReorderFieldsDisplay() })
  }

  function OngkosKirimId_CreateInputCityText (type) {
    var type_tag = '#' + type + '_'
    var input = '<input type="text" class="input-text " name="' + type + '_city_text" id="' + type + '_city_text" placeholder=""  value="" autocomplete="address-level2" style="display: none;" />'
    $(type_tag + 'city').parent().append(input)
    $(type_tag + 'city_text').hide()
  }

  function OngkosKirimId_CountryOnchange (type) {
    var type_tag = '#' + type + '_'
    $(type_tag + 'country').change(function () {
      if ($(type_tag + 'country').val() != 'ID') {
        // required city hack
        $(type_tag + 'city').html('').select2().select2('destroy').append("<option value='-'>-</option>").val('-').change().hide()
        $(type_tag + 'city_text').val('').show()

        $(type_tag + 'district_field').hide()
        $(type_tag + 'district').append("<option value='-'>-</option>").val('-')
      } else {
        $(type_tag + 'city_text').val('').hide()

        OngkosKirimId_DisableCity(type)
        OngkosKirimId_DisableDistrict(type)
      }
      return true
    })
  }
  function OngkosKirimId_StateOnChange (type) {
    var type_tag = '#' + type + '_'
    $(type_tag + 'state').change(function () {
      if ($(type_tag + 'country').val() != 'ID') { return true }

      var cities 	= new Array()
      var state	= $(this).val()

      OngkosKirimId_DisableDistrict(type)

      if (state == null || typeof state === 'undefined' || state == '') { return true }
      // if( state == 'KU' ) state = 'KA';

      if (debug) console.log('ref_city[state]')
      if (debug) console.log(ref_city)
      if (debug) console.log(state)

      $.each(ref_city[state], function (key, value) {
        var tmp		= new Object()
        tmp.id	= key
        tmp.text	= value
        cities.push(tmp)
      })

      if (debug) console.log('cities')
      if (debug) console.log(cities)

      OngkosKirimId_EnableCity(type)
      $(type_tag + 'city').select2({
        data: cities
      })
    })
  }

  function OngkosKirimId_CityOnChange (type) {
    var type_tag = '#' + type + '_'
    $(type_tag + 'city').change(function () {
      if ($(type_tag + 'country').val() != 'ID') { return true }

      var city_id 	= $(this).val()

      OngkosKirimId_DisableDistrict()

      if (city_id < 1) { return true }

      if (debug) console.log('city_id')
      if (debug) console.log(city_id)

      OngkosKirimId_LoadDistrict(city_id, type)
    })
  }

  // existing customer
  function OngkosKirimId_LoadExistingCustomer () {
    var cust = ongkoskirim_id.customer

    if (cust.logged_in != 1) {
      if ($('#billing_state').val() != '') {
        $('#billing_state').change()
      }

      if ($('#shipping_state').val() != '') { $('#shipping_state').change() }

      return true
    }

    OngkosKirimId_LoadExistingType(cust, 'billing')
    OngkosKirimId_LoadExistingType(cust, 'shipping')
  }

  function OngkosKirimId_LoadExistingType (cust, type) {
    var country = type + '_country'
    var state = type + '_state'
    var city = type + '_city'
    var city_text = type + '_city_text'
    var district = type + '_district'

    $('#' + country).change()

    if (cust[country] == '') { return true }

    if ($('#' + country).val() != 'ID') { $('#' + city_text).val(cust[city]) }

    if (cust[state] == '') { return true }

    $('#' + state).val(cust[state]).change()

    if (cust[city] == '') { return true }

    if ($('#' + country).val() == 'ID') { $('#' + city).val(cust[city]).change() }

    if (cust[district] == '') { return true }

    $('#' + district).on('after_district_populate', function () {
      $('#' + district).val(cust[district]).change()
    })
  }

  function OngkosKirimId_LoadCity () {
    console.log(ongkoskirim_id)
    ref_city	= ongkoskirim_id.cities.city
    ref_state	= ongkoskirim_id.cities.state
  }

  function OngkosKirimId_LoadDistrict (city_id, type) {
    var type_tag = '#' + type + '_'

    var data = {
      'action': 'get_districts',
      'city_id': city_id
    }
    $('#billing_district_field label').html('Loading...')
    jQuery.post(ongkoskirim_id.ajax_url, data, function (data) {
      $('#billing_district_field label').html('Kecamatan <abbr class="required" title="required">*</abbr>')
      var districts = new Array()

      ref_district[city_id]	= data

      $.each(ref_district[city_id], function (key, value) {
        var tmp		= new Object()
        tmp.id	= key
        tmp.text	= value
        districts.push(tmp)
      })

      OngkosKirimId_EnableDistrict(type)
      $(type_tag + 'district').select2({
        data: districts
      }).trigger('after_district_populate')
    }, 'json')
  }

  function OngkosKirimId_DisableDistrict (type) {
    var type_tag = '#' + type + '_'
    $(type_tag + 'district_field').show()
    $(type_tag + 'district').html('').show().append("<option value='0'>Pilih Kecamatan</option>").select2().prop('disabled', true)
  }

  function OngkosKirimId_EnableDistrict (type) {
    var type_tag = '#' + type + '_'
    $(type_tag + 'district').html('').show().append("<option value='0'>Pilih Kecamatan</option>").select2().prop('disabled', false)
  }

  function OngkosKirimId_DisableCity (type) {
    var type_tag = '#' + type + '_'
    $(type_tag + 'city').html('').show().append("<option value='0'>Pilih Kota</option>").select2().prop('disabled', true)
  }

  function OngkosKirimId_EnableCity (type) {
    var type_tag = '#' + type + '_'
    $(type_tag + 'city').html('').show().append("<option value='0'>Pilih Kota</option>").select2().removeProp('disabled').removeAttr('disabled')
  }

  function OngkosKirimId_ReorderFieldsDisplay () {
    // reorder dropdown provinsi
    console.log('reorder dropdown')
    var w = $('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields__field-wrapper')
    w.each(function (i, w) {
      var html = $(w).find('.form-row').sort(function (a, b) {
        var ka = parseInt($(a).attr('data-priority')) || parseInt($(a).attr('data-sort'))
        var ki = parseInt($(b).attr('data-priority')) || parseInt($(b).attr('data-sort'))
        return ka - ki
      })
      html.appendTo(w)
    })
  }
})(jQuery)
