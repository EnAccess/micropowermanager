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
        {{ $tc("words.phone") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.payerPhoneNumber || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.deviceSerial") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.serialNumber || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.referenceId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.referenceId || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.conversationId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.conversationId || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.externalTransactionId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.transactionId || "-" }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.status") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ statusLabel }}
      </div>
    </div>
  </div>
</template>

<script>
import vodacomLogo from "@/assets/icons/vodacom.png"

export default {
  name: "VodacomMzTransactionDetail",
  props: ["ot"],
  data() {
    return {
      imgLogo: vodacomLogo,
      statusLabels: {
        "-1": "Failed",
        0: "Requested",
        1: "Successful",
        2: "Completed",
        3: "Abandoned",
      },
    }
  },
  computed: {
    statusLabel() {
      return this.statusLabels[this.ot.status] ?? this.ot.status
    },
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
