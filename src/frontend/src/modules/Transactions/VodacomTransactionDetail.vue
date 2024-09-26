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
        {{ $tc("phrases.originatorConversationId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.originator_conversation_id }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.conversationId") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.conversation_id }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.mpesaReceipt") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ ot.mpesa_receipt }}
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
import vodacomLogo from "@/assets/icons/vodacom.png"

export default {
  name: "VodacomTransactionDetail",
  props: ["ot"],
  data() {
    return {
      imgLogo: vodacomLogo,
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
  /* transition: margin-left .3s cubic-bezier(.4,0,.2,1); */
  /* will-change: margin-left; */
  background-color: rgba(0, 0, 0, 0.12);
}
</style>
