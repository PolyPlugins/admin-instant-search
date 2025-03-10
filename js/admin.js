jQuery(function ($) {

  var orderIndex;
  var orderIndexLoading = false;
  var orderIndexLoaded  = false;

  $(document).ready(function () {
    addSearchContainer();
    addSearchButton();
    loadIndex();
    searchInit();
    searchClose();
  });

  function addSearchContainer() {
    $('.search-box').append(`
      <div class="instant-order-search-container" style="display: none;">
        <a href="javascript:void(0)" class="close-btn" title="Close"><span class="dashicons dashicons-no"></span></a>
        <div class="instant-order-search-results-container">
          <input type="search" id="instant-search-input" placeholder="Search for order...">
          <div class="spinner"></div>
          <div class="instant-order-search-results"></div>
        </div>
      </div>
    `)
  }

  function addSearchButton() {
    $("#search-submit").before(`
      <input type="button" id="instant-search" class="button" value="Instant Order Search">
    `);
  }

  function loadIndex() {
    $('#instant-search').on('click', function(e) {
      e.preventDefault();

      $("body").addClass('no-scroll');

      $(".instant-order-search-container").slideDown(function() {
        if (orderIndexLoading) {
          $(".spinner").addClass('is-active');
          $('#instant-search-input').attr("disabled", true);
        }
      });

      if (!orderIndexLoaded) {
        orderIndexLoaded  = true;
        orderIndexLoading = true;

        $.ajax({
          url: instantOrderSearch.ajax_url,
          method: 'POST',
          data: {
            action: 'instant_order_search',
            nonce: instantOrderSearch.nonce,
          },
          success: function(response) {
            if (response.success) {
              orderIndex = response.data;
            } else {
              if (response.data) {
                alert(response.data);
              } else {
                alert('An error occurred fetching order index.')
              }
            }
          },
          complete: function() {
            orderIndexLoading = false;
            $(".spinner").removeClass('is-active');
            $('#instant-search-input').attr("disabled", false);
            $('#instant-search-input').focus()
          }
        });
      }
    });
  }

  function searchInit() {
    $('#instant-search-input').on('keyup', searchResults);
  }

  function searchClose() {
    $('.close-btn').on('click', function() {
      $(".instant-order-search-container").slideUp(function() {
        $("body").removeClass('no-scroll');
      });

      $('.instant-order-search-results').empty();
      $('#instant-search-input').val('');
    });
  }

  function searchResults() {
    const searchValue = $('#instant-search-input').val().toLowerCase();
    const filteredData = [];

    $.each(orderIndex, function(key, value) {
        if (
          value.order_number.toString().toLowerCase().includes(searchValue) ||
          value.name.toLowerCase().includes(searchValue) ||
          value.billing_address.toLowerCase().includes(searchValue) ||
          value.billing_email.toLowerCase().includes(searchValue) ||
          value.billing_phone.includes(searchValue) ||
          value.order_status.includes(searchValue)
        ) {
          filteredData.push(value);
        }
    });

    // Sort descending
    filteredData.sort((a, b) => {
      return b.order_number - a.order_number;
    });

    displayResults(filteredData);
  }

  function displayResults(filteredData) {
    $('.instant-order-search-results').empty();

    if (filteredData.length === 0) {
      $('.instant-order-search-results').append('<p>No results found</p>');
    } else {
      $.each(filteredData, function(index, item) {
        const resultHtml = `
          <a href="post.php?post=${item.order_number}&action=edit" class="instant-order-search-result ${item.order_status}" target="_blank">
            <div class="order-number">#${item.order_number}</div>
            <div class="name">${item.name}</div>
            <div class="billing-address">${item.billing_address}</div>
            <div class="email">${item.billing_email}</div>
            <div class="phone">${item.billing_phone}</div>
            <div class="total">${item.order_total}</div>
            <div class="status">${item.order_status}</div>
          </a>
        `;
        $('.instant-order-search-results').append(resultHtml);
      });

      $('.instant-order-search-results').append(`<div class="service-container"><div class="services">Built by <a href="https://www.polyplugins.com" target="_blank">Poly Plugins</a>. Now offering <a href="https://calendly.com/poly-plugins/wordpress-maintenance-discovery-call" target="_blank">WordPress Maintenance Services</a> starting at $49 a month. Don't let auto updates take your site down.</div></div>`);
    }
  }

});