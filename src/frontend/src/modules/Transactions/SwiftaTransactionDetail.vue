<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.swiftaTransaction") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <img
          class="logo"
          alt="logo"
          :src="swiftaLogo"
          style="max-height: 22px"
        />
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionReference") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.transaction_reference }}
      </div>
    </div>
  </div>
</template>

<script>
import swiftaLogo from "@/assets/icons/Swifta.png"

export default {
  name: "SwiftaTransactionDetail",
  props: ["ot"],
  data() {
    return {
      swiftaLogo: swiftaLogo,
    }
  },
}
</script>

<style scoped></style>
