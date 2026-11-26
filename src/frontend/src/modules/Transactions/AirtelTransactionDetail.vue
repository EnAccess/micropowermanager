<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">Airtel Money</div>
      <div class="md-layout-item md-subheader n-font">
        {{ statusLabel }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.tr_id }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionReference") }}
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
const STATUS_LABELS = {
  1: "Success",
  "-1": "Failed",
  0: "Processing",
  2: "Validated, waiting for Airtel",
}

export default {
  name: "AirtelTransactionDetail",
  props: ["ot"],
  computed: {
    statusLabel() {
      return STATUS_LABELS[this.ot.status] ?? ""
    },
  },
}
</script>

<style scoped lang="scss"></style>
