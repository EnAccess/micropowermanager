<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.mesombTransaction") }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionIdentifier") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.pk || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.transactionReference") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.reference || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.paymentService") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.service || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.phone") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.b_party || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.fee") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.fees === null || ot.fees === undefined ? "-" : ot.fees }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "MesombTransactionDetail",
  props: ["ot"],
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
