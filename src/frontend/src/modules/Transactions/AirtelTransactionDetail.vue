<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.mobileProvider") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <img class="logo" alt="logo" :src="imgLogo" style="max-height: 35px" />
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <span>{{ ot.tr_id }}</span>
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionIdentifier") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.trans_id }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.businessNumber") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.business_number }}
      </div>
    </div>
  </div>
</template>

<script>
import airtelLogo from "@/assets/icons/airtel.png"

export default {
  name: "AirtelTransactionDetail",
  props: ["ot"],
  data() {
    return {
      imgLogo: airtelLogo,
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
</style>
