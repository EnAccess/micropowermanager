<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.waveMoneyTransaction") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <img
          class="logo"
          alt="logo"
          :src="waveMoneyLogo"
          style="max-height: 48px"
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
  </div>
</template>

<script>
import waveMoneyLogo from "@/assets/icons/WaveMoney.png"
export default {
  name: "WaveMoneyTransactionDetail",
  props: ["ot"],
  data() {
    return {
      waveMoneyLogo: waveMoneyLogo,
    }
  },
}
</script>

<style scoped></style>
