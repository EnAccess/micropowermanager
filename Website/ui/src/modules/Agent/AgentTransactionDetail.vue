<template>
  <div>
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("phrases.agentTransaction") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        <img
          class="logo"
          alt="logo"
          :src="agentLogo"
          style="max-height: 35px"
        />
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.name") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ agentService.agent.name }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.phone") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ agentService.agent.phone }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.email") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ agentService.agent.email }}
      </div>
    </div>
    <hr class="hr-d" />
    <div class="md-layout">
      <div class="md-layout-item md-subheader">
        {{ $tc("words.miniGrid") }}
      </div>
      <div class="md-layout-item md-subheader n-font">
        {{ agentService.agent.miniGrid }}
      </div>
    </div>
  </div>
</template>

<script>
import { AgentService } from "@/services/AgentService"
import agentLogo from "@/assets/icons/agent-icon.png"
export default {
  name: "AgentTransactionDetail",
  props: ["ot"],
  data() {
    return {
      agentService: new AgentService(),
      agentLogo: agentLogo,
    }
  },
  mounted() {
    this.getAgentDetail()
  },
  methods: {
    async getAgentDetail() {
      try {
        await this.agentService.getAgent(this.ot.agent_id)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
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
