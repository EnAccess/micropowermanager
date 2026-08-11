<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.safaricomTransaction") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <img
          class="logo"
          alt="logo"
          :src="safaricomLogo"
          style="max-height: 32px"
        />
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.orderId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.order_id }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.referenceId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.reference_id }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.currency") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.currency }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.phone") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.phone_number || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.mpesaReceipt") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.mpesa_receipt_number || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.checkoutRequestId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.checkout_request_id || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.externalTransactionId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.external_transaction_id || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.deviceType") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.device_type || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.deviceSerial") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.serial_id || "-" }}
      </div>
    </div>
  </div>
</template>

<script>
import safaricomLogo from "@/assets/icons/safaricom.svg"

export default {
  name: "SafaricomTransactionDetail",
  props: ["ot"],
  data() {
    return {
      safaricomLogo: safaricomLogo,
    }
  },
}
</script>

<style scoped lang="scss">
.n-font {
  font-weight: 100 !important;
}

.hr-d {
  height: 1pt;
  margin: auto;
  padding: 0;
  display: block;
  border: 0;
  background-color: rgba(0, 0, 0, 0.12);
}
</style>
