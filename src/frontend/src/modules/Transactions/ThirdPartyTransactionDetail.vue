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
        {{ $tc("words.status") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <span v-if="ot.status === 1" class="status-success">
          {{ $tc("words.confirm", 2) }}
        </span>
        <span v-else-if="ot.status === 0" class="status-pending">
          {{ $tc("words.process", 3) }}
        </span>
        <span v-else class="status-failed">
          {{ $tc("words.reject", 2) }}
        </span>
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
        {{ $tc("phrases.createdBy") }}
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

.status-success {
  color: #4caf50;
  font-weight: 500;
}

.status-pending {
  color: #ff9800;
  font-weight: 500;
}

.status-failed {
  color: #f44336;
  font-weight: 500;
}
</style>
