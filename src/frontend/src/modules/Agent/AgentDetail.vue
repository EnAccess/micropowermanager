<template>
  <widget
    :title="$tc('words.detail', 2)"
    :button="true"
    :button-text="$tc('words.delete')"
    button-icon="delete"
    @widgetAction="confirmDelete"
  >
    <md-card>
      <md-card-content>
        <div class="md-layout md-gutter md-alignment-center" v-if="!editAgent">
          <div class="md-layout-item">
            <div class="md-layout md-alignment-center-left">
              <div class="md-layout-item md-size-20">
                <md-avatar class="md-avatar-icon md-large">
                  {{ initials }}
                </md-avatar>
              </div>
              <div class="md-layout-item">
                <h2>
                  {{ agent.name }}
                  {{ agent.surname }}
                </h2>
              </div>
            </div>
          </div>

          <div class="md-layout-item md-size-20">
            <md-button
              @click="editAgent = true"
              class="md-icon-button"
              style="float: right"
            >
              <md-icon>create</md-icon>
            </md-button>
          </div>

          <div class="md-layout-item md-size-100">
            <md-list class="md-double-line">
              <md-list-item>
                <md-icon>wc</md-icon>
                <div class="md-list-item-text">
                  <span>{{ $tc("words.gender") }}</span>
                  <span>{{ agent.gender || "N/A" }}</span>
                </div>
              </md-list-item>
              <md-divider></md-divider>
              <md-list-item>
                <md-icon>phone</md-icon>
                <div class="md-list-item-text">
                  <span>{{ $tc("words.phone") }}</span>
                  <span>
                    {{ agent.phone || "N/A" }}
                  </span>
                </div>
              </md-list-item>
              <md-divider></md-divider>
              <md-list-item>
                <md-icon>cake</md-icon>
                <div class="md-list-item-text">
                  <span>{{ $tc("words.birthday") }}</span>
                  <span>
                    {{ agent.birthday || "N/A" }}
                  </span>
                </div>
              </md-list-item>
              <md-divider></md-divider>
              <md-list-item>
                <md-icon>tag</md-icon>
                <div class="md-list-item-text">
                  <span>{{ $tc("words.type") }}</span>
                  <span>
                    {{ agent.commissionType || "N/A" }}
                  </span>
                </div>
              </md-list-item>
              <md-divider></md-divider>
              <md-list-item>
                <md-icon>account_balance_wallet</md-icon>
                <div class="md-list-item-text">
                  <span>{{ $tc("words.balance") }}</span>
                  <span>
                    {{ formatCurrency(agent.balance || 0) }}
                  </span>
                </div>
              </md-list-item>
            </md-list>
          </div>
        </div>

        <div class="md-layout md-gutter" v-else>
          <div class="md-layout-item md-size-100">
            <form novalidate class="md-layout" @submit.prevent="updateAgent">
              <md-card class="md-layout-item md-size-100">
                <md-card-content>
                  <md-field>
                    <label for="name">
                      {{ $tc("words.name") }}
                    </label>
                    <md-input
                      type="text"
                      name="name"
                      id="name"
                      v-model="agent.name"
                    />
                  </md-field>

                  <md-field>
                    <label for="surname">
                      {{ $tc("words.surname") }}
                    </label>
                    <md-input
                      type="text"
                      name="surname"
                      id="surname"
                      v-model="agent.surname"
                    />
                  </md-field>

                  <md-datepicker
                    name="birthDate"
                    md-immediately
                    v-model="agent.birthday"
                    :md-close-on-blur="false"
                  >
                    <label for="birth-date">Birthday :</label>
                  </md-datepicker>

                  <md-field>
                    <label for="gender">{{ $tc("words.gender") }} :</label>
                    <md-select name="gender" id="gender" v-model="agent.gender">
                      <md-option disabled v-if="agent.gender == null">
                        -- Select --
                      </md-option>
                      <md-option value="male">
                        {{ $tc("words.male") }}
                      </md-option>
                      <md-option value=" female">
                        {{ $tc("words.female") }}
                      </md-option>
                    </md-select>
                  </md-field>

                  <md-field v-if="$can('settings')">
                    <label for="commission">
                      {{ $tc("phrases.commissionType") }}:
                    </label>
                    <md-select
                      name="commission"
                      id="commission"
                      v-model="agent.commissionTypeId"
                    >
                      <md-option
                        v-for="commission in agentCommissions"
                        :value="commission.id"
                        :key="commission.id"
                      >
                        {{ commission.name }}
                      </md-option>
                    </md-select>
                  </md-field>
                  <md-field>
                    <label for="phone">
                      {{ $tc("words.phone") }}
                    </label>
                    <md-input
                      type="text"
                      name="phone"
                      id="phone"
                      v-model="agent.phone"
                    />
                  </md-field>
                </md-card-content>
                <md-progress-bar md-mode="indeterminate" v-if="loading" />
                <md-card-actions>
                  <md-button
                    type="submit"
                    class="md-primary btn-save"
                    :disabled="loading"
                  >
                    {{ $tc("words.save") }}
                  </md-button>
                  <md-button
                    type="button"
                    @click="editAgent = false"
                    class="md-accent btn-save"
                  >
                    {{ $tc("words.cancel") }}
                  </md-button>
                </md-card-actions>
              </md-card>
            </form>
          </div>
        </div>
      </md-card-content>
    </md-card>
  </widget>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import { AgentService } from "@/services/AgentService"
import { AgentCommissionService } from "@/services/AgentCommissionService"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"
import { currency } from "@/mixins/currency"

export default {
  name: "AgentDetail",
  mixins: [notify, currency],
  components: { Widget },
  data() {
    return {
      agentService: new AgentService(),
      agentCommissionService: new AgentCommissionService(),
      agent: {},
      agentCommissions: [],
      editAgent: false,
      loading: false,
    }
  },
  props: {
    agentId: {
      default: null,
    },
  },
  mounted() {
    this.getAgentDetail()
    this.getAgentCommissions()
    EventBus.$on("balanceAdded", () => {
      this.getAgentDetail()
    })
    EventBus.$on("receiptAdded", () => {
      this.getAgentDetail()
    })
  },
  computed: {
    initials() {
      const person = this.agent
      if (!person) return ""

      const first = person.name?.charAt(0) ?? ""
      const last = person.surname?.charAt(0) ?? ""

      return (first + last).toUpperCase()
    },
  },
  methods: {
    async getAgentCommissions() {
      if (!this.$can("settings")) {
        return
      }
      try {
        this.agentCommissions =
          await this.agentCommissionService.getAgentCommissions()
      } catch (e) {
        if (e.response && e.response.status === 403) {
          console.warn("Agent commissions: Insufficient permissions")
          return
        }
        this.alertNotify("error", e.message)
      }
    },
    async getAgentDetail() {
      try {
        this.agent = await this.agentService.getAgent(Number(this.agentId))
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    confirmDelete() {
      const agentFullName =
        `${this.agent.name || ""} ${this.agent.surname || ""}`.trim()
      this.$swal({
        type: "question",
        title: this.$tc("phrases.confirmDeleteAgent"),
        width: "35%",
        confirmButtonText: this.$tc("words.delete"),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        focusCancel: true,
        html:
          '<div style="text-align: left; padding-left: 5rem" class="checkbox">' +
          "  <label>" +
          '    <input type="checkbox" name="confirmation" id="confirmation" >' +
          this.$tc("phrases.confirmDeleteAgentText", 1, {
            name: agentFullName,
          }) +
          "  </label>" +
          "</div>",
      }).then((result) => {
        let answer = document.getElementById("confirmation").checked
        if ("value" in result && answer) {
          this.deleteAgent()
        }
      })
    },
    async updateAgent() {
      try {
        this.loading = true
        await this.agentService.updateAgent(this.agent)
        this.alertNotify("success", this.$tc("phrases.deleteAgent", 2))
        this.loading = false
        this.editAgent = false
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async deleteAgent() {
      try {
        await this.agentService.deleteAgent(this.agent)
        this.alertNotify("success", this.$tc("phrases.deleteAgent", 1))
        window.history.back()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    formatCurrency(amount) {
      const currency =
        this.$store.getters["settings/getMainSettings"]?.currency || "TZS"
      return this.readable(amount) + currency
    },
  },
}
</script>
<style scoped></style>
