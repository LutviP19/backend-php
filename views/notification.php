<?php $this->include('includes.header')?>
<br><br>
<div class="container">
    <h3 class="center">FCM Notification</h3>
    <p class="center">This page for demo FCM!</p>
    <p class="center">------------------</p>

    <!-- Verify number screen content start -->
    <section id="verify-number" style="padding: 33px 0;">
      <div class="container">
        <div class="verify-number-wrap">
          <div class="verify-img mt-32">
            <img src="<?=assets('/assets/icons/icon-512.png');?>" alt="notification-img" width="300">
          </div>
          <div class="verify-txt mt-32">
            <h1>Notifications</h1>
            <p class="mt-16">Stay notified about new car, offer status and other updates. You can turn off any time from
              setting.</p>
          </div>

          <form class="map-section-form mt-24">
            <?=csrfField();?>
            <div class="footer-checkbox-sec mt-16">
              <input type="hidden" id="allow_notification" value="inactive">

              <p class="h6 text-center">Device Notifications Status:</p>
              <div class="text-center">
                <span id="tokenBox" class="small">Notification not set.</span>
              </div>

			</div>
          </form>

          <div class="verify-txt mt-32">
            <div id="token"></div>
            <div id="message"></div>
            <div id="error"></div>
          </div>
          <div class="verify-number-btn">
            <div class="d-flex justify-content-center p-2">
              <span id="errAllow" class="small text-danger"></span>
            </div>
            <a class="btn btn-primary" role="button" id="nextBtn" href="javascript:void(0)">Allow</a>
          </div>
        </div>
      </div>
    </section>
    <!-- Verify number screen content end -->
</div>
<script src="<?= assets('/assets/js/axios.min.js?v=5') ?>"></script>
<script type="module" src="<?= assets('/assets/js/firebase.js?v='.time()) ?>"></script>
<script>
const nextBtn = document.getElementById("nextBtn");
const params = new URLSearchParams(window.location.search);
const regType = params.get('regType') ?? 'userx';
const regIdQs = params.get('regId') ?? 'NDVhZWM4MzY4MTY3MDQ4NTgwZjY1MGU5ZDFlZTAwZmU2YWJmOWJiZGIzNTljMjgyN2FlNzAzODNhMmQzMjEzYg==';

function handleClick() {
  const allow_notification = $("#allow_notification").val();

  $("#errAllow").empty();
  if (!regIdQs || regIdQs === undefined || regIdQs === null || regIdQs == '') {
    $("#errAllow").text('Invalid registration id, please goback!');
    return;
  }

  const apiUrl = regType == 'customer' ? '/api/v1/reg/customer/step2/' : '/api/v1/reg/driver/step2/';

  axios.post(apiUrl + regIdQs, {
    allow_notification: allow_notification,
    regId: regIdQs
  })
  .then(function (response) {
    var regId = response.data.data.regId;
    
    const nextUrl = regType == 'customer' ? '/create-new-pin' : '/create-new-pin';
    window.location.href = nextUrl + "?regId=" + regId + "&regType=" + regType;
  })
  .catch(function (error) {
    const errors = error.response.data.errors;
    
    if (typeof errors === "string") {
      $("#errAllow").text(errors);
    } else {

      errors.forEach((obj, index) => {
        for (const key in obj) {
          if (Object.hasOwnProperty.call(obj, key)) {
            const value = obj[key];

            if (key === "allow_notification") {
              $("#errAllow").text(value);
              return;
            }

            if (key === "regId") {
              $("#errAllow").text(value);
              return;
            }

            if (key === "busy") {
              $("#errAllow").text(value);
            }

            if (key === "csrfToken") {
				window.location.reload(true);
			}

          }
        }
      });
    }
  });
}

// nextBtn.addEventListener('click', handleClick);
</script>
<?php $this->include('includes.footer')?>