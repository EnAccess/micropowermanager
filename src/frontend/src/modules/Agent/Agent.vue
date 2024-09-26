<template>
  <div>
    <div class="md-layout md-gutter">
      <div
        class="md-layout-item md-large-size-50 md-medium-size-50 md-xlarge-size-50 md-small-size-100 md-small-size-100"
      >
        <agent-detail :agent-id="agentId" />
      </div>

      <div
        class="md-layout-item md-large-size-50 md-medium-size-50 md-xlarge-size-50 md-small-size-100 md-small-size-100"
      >
        <agent-receipt-list :agent-id="agentId" />
      </div>
      <div
        class="md-layout-item md-large-size-50 md-medium-size-50 md-xlarge-size-50 md-small-size-100 md-small-size-100"
      >
        <agent-balance-history-list :agent-id="agentId" />
      </div>

      <div
        class="md-layout-item md-large-size-50 md-medium-size-50 md-xlarge-size-50 md-small-size-100 md-small-size-100"
      >
        <assigned-appliance-list :agent-id="agentId" />
        <sold-appliance-list :agent-id="agentId" />
        <agent-ticket-list :agent-id="agentId" />
      </div>
      <div
        class="md-layout-item md-large-size-100 md-medium-size-100 md-xlarge-size-100 md-small-size-100 md-small-size-100"
      >
        <agent-transaction-list :agent-id="agentId" />
      </div>
    </div>
  </div>
</template>
<script>
import AgentDetail from "./AgentDetail"
import AssignedApplianceList from "./Appliances/AssignedApplianceList"
import AgentReceiptList from "./Receipt/AgentReceiptList"
import SoldApplianceList from "./Appliances/SoldApplianceList"
import AgentTransactionList from "./AgentTransactionList"
import AgentTicketList from "./AgentTicketList"
import AgentBalanceHistoryList from "./Balance/AgentBalanceHistory"

export default {
  name: "Agent",
  data() {
    return {
      agentId: null,
    }
  },
  components: {
    AgentBalanceHistoryList,
    AgentTicketList,
    AgentTransactionList,
    SoldApplianceList,
    AgentReceiptList,
    AssignedApplianceList,
    AgentDetail,
  },
  created() {
    this.agentId = this.$route.params.id
  },
}
</script>
<style scoped></style>
