<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.paystackTransaction") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <img
          class="logo"
          alt="logo"
          :src="paystackLogo"
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
        {{ $tc("phrases.paystackReference") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.paystack_reference || "-" }}
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
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.paymentUrl") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <a
          v-if="ot.payment_url"
          :href="ot.payment_url"
          target="_blank"
          class="payment-link"
        >
          {{ $tc("phrases.viewPaymentLink") }}
        </a>
        <span v-else>-</span>
      </div>
    </div>
  </div>
</template>

<script>
import paystackLogo from "@/assets/icons/Paystack.png"

export default {
  name: "PaystackTransactionDetail",
  props: ["ot"],
  data() {
    return {
      paystackLogo: paystackLogo,
    }
  },
}
</script>

<style scoped>
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

.payment-link {
  color: #1976d2;
  text-decoration: none;
}

.payment-link:hover {
  text-decoration: underline;
}
</style>
