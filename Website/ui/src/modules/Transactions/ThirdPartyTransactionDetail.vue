<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.thirdPartyTransaction") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot?.manufacturer_transaction_type ?? "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot?.transaction_id ?? "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.description") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot?.description ?? "-" }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ThirdPartyTransactionDetail",
  props: ["ot"],
  data() {
    return {}
  },
}
</script>

<style scoped></style>
