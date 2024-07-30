<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.waveComTransaction") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <img
          class="logo"
          alt="logo"
          :src="waveComLogo"
          style="max-height: 36px"
        />
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionReference") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.message }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.transaction_id }}
      </div>
    </div>
  </div>
</template>

<script>
import waveComLogo from "@/assets/icons/WaveComLogo.png"

export default {
  name: "WaveComTransactionDetail",
  props: ["ot"],
  data() {
    return {
      waveComLogo: waveComLogo,
    }
  },
}
</script>

<style scoped></style>
