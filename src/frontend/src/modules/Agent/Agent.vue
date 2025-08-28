<template>
  <div>
    <!-- Agent Actions Header -->
    <div class="agent-actions-header">
      <div class="actions-container">
        <md-button 
          class="md-raised md-primary create-payment-btn"
          @click="goToCreatePayment"
        >
          <md-icon>payment</md-icon>
          {{ $tc("phrases.createPaymentLink") }}
        </md-button>
      </div>
    </div>

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
  methods: {
    goToCreatePayment() {
      this.$router.push(`/agents/${this.agentId}/create-payment`)
    },
  },
}
</script>
<style scoped>
.agent-actions-header {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.actions-container {
  display: flex;
  gap: 1rem;
  align-items: center;
  justify-content: flex-start;
}

.create-payment-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .actions-container {
    flex-direction: column;
    align-items: stretch;
  }
  
  .create-payment-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
