<template>
  <div>
    <new-commission :addNewCommission="showNewCommission" />
    <widget
      :class="'col-sm-6 col-md-5'"
      :button-text="$tc('phrases.addCommissionType')"
      :button="true"
      :title="$tc('phrases.commissionType', 2)"
      @widgetAction="newCommission"
      color="green"
      :subscriber="subscriber"
    >
      <md-progress-bar md-mode="indeterminate" v-if="loading" />

      <md-table md-sort="id" md-sort-order="asc">
        <md-table-row>
          <md-table-head v-for="(item, index) in headers" :key="index">
            {{ item }}
          </md-table-head>
        </md-table-row>
        <md-table-row
          v-for="(item, index) in agentCommissionService.list"
          :key="index"
        >
          <md-table-cell md-label="Name">
            <div v-if="editCommission === item.id">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.name')),
                }"
              >
                <label for="name">
                  {{ $tc("words.name") }}
                </label>
                <md-input
                  :name="$tc('words.name')"
                  id="name"
                  v-model="item.name"
                  v-validate="'required|min:3'"
                />
                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div v-else>
              {{ item.name }}
            </div>
          </md-table-cell>
          <md-table-cell md-label="Energy Commission">
            <div v-if="editCommission === item.id">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('phrases.energyCommission')),
                }"
              >
                <label>
                  {{ $tc("phrases.energyCommission") }}
                </label>
                <md-input
                  :name="$tc('phrases.energyCommission')"
                  id="energyCommission"
                  v-model="item.energyCommission"
                  v-validate="'required|min_value:0'"
                  type="number"
                />
                <span class="md-error">
                  {{ errors.first($tc("phrases.energyCommission")) }}
                </span>
              </md-field>
            </div>
            <div v-else>
              {{ item.energyCommission }}
            </div>
          </md-table-cell>
          <md-table-cell md-label="Appliance Commission">
            <div v-if="editCommission === item.id">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('phrases.applianceCommission')),
                }"
              >
                <label>
                  {{ $tc("phrases.applianceCommission") }}
                </label>
                <md-input
                  :name="$tc('phrases.applianceCommission')"
                  id="applianceCommission"
                  v-model="item.applianceCommission"
                  v-validate="'required|min_value:0'"
                  type="number"
                />
                <span class="md-error">
                  {{ errors.first($tc("phrases.applianceCommission")) }}
                </span>
              </md-field>
            </div>
            <div v-else>
              {{ item.applianceCommission }}
            </div>
          </md-table-cell>
          <md-table-cell md-label="Risk Balance">
            <div v-if="editCommission === item.id">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('phrases.riskBalance')),
                }"
              >
                <label>
                  {{ $tc("phrases.riskBalance") }} ({{
                    $tc("phrases.mustBeNegative")
                  }})
                </label>
                <md-input
                  :name="$tc('phrases.riskBalance')"
                  id="riskBalance"
                  max="0"
                  v-model="item.riskBalance"
                  v-validate="'required|max_value:0'"
                  type="number"
                />
                <span class="md-error">
                  {{ errors.first($tc("phrases.riskBalance")) }}
                </span>
              </md-field>
            </div>
            <div v-else>
              {{ item.riskBalance }}
            </div>
          </md-table-cell>
          <md-table-cell md-label="#">
            <div v-if="editCommission === item.id">
              <md-button class="md-icon-button" @click="updateCommission(item)">
                <md-tooltip md-direction="top">
                  {{ $tc("words.save") }}
                </md-tooltip>
                <md-icon>save</md-icon>
              </md-button>
              <md-button class="md-icon-button" @click="editCommission = null">
                <md-tooltip md-direction="top">
                  {{ $tc("words.close") }}
                </md-tooltip>
                <md-icon>close</md-icon>
              </md-button>
            </div>
            <div v-else>
              <md-button
                class="md-icon-button"
                @click="editCommission = item.id"
              >
                <md-tooltip md-direction="top">
                  {{ $tc("words.edit") }}
                </md-tooltip>
                <md-icon>edit</md-icon>
              </md-button>
              <md-button class="md-icon-button" @click="confirmDelete(item)">
                <md-tooltip md-direction="top">
                  {{ $tc("words.delete") }}
                </md-tooltip>
                <md-icon>delete</md-icon>
              </md-button>
            </div>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { AgentCommissionService } from "@/services/AgentCommissionService"
import NewCommission from "../Commission/NewCommission"
import { notify } from "@/mixins/notify"
export default {
  name: "AgentCommissionList",
  mixins: [notify],
  data() {
    return {
      subscriber: "agent-commission-list",
      agentCommissionService: new AgentCommissionService(),
      showNewCommission: false,
      headers: [
        this.$tc("words.name"),
        this.$tc("phrases.energyCommission"),
        this.$tc("phrases.applianceCommission"),
        this.$tc("phrases.riskBalance"),
        "#",
      ],
      tableName: "Agent Commission Types",
      editCommission: null,
      loading: false,
    }
  },
  components: {
    Widget,
    NewCommission,
  },
  mounted() {
    this.getAgentCommissions()
    EventBus.$on("commissionAdded", this.closeNewCommission)
    EventBus.$on("newCommissionClosed", () => {
      this.showNewCommission = false
    })
  },
  beforeDestroy() {
    EventBus.$off("commissionAdded", this.closeNewCommission)
  },
  methods: {
    async closeNewCommission() {
      await this.getAgentCommissions()
      this.showNewCommission = false
    },
    async newCommission() {
      this.agentCommissionService.resetAgentCommission()
      this.showNewCommission = true
    },
    async getAgentCommissions() {
      try {
        await this.agentCommissionService.getAgentCommissions()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.agentCommissionService.list.length,
        )
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async updateCommission(commission) {
      try {
        this.loading = true
        await this.agentCommissionService.updateAgentCommission(commission)
        this.alertNotify("success", this.$tc("phrases.agentCommissionUpdated"))
        await this.getAgentCommissions()
        this.loading = false
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async confirmDelete(commission) {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteAgentCommission"),
        width: "35%",
        confirmButtonText: this.$tc("words.confirm"),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        focusCancel: true,
        html:
          '<div style="text-align: left; padding-left: 5rem" class="checkbox">' +
          "  <label>" +
          '    <input type="checkbox" name="confirmation" id="confirmation" >' +
          this.$tc("phrases.deleteAgentCommission", 2, {
            commissionName: commission.name,
          }) +
          "  </label>" +
          "</div>",
      }).then((result) => {
        let answer = document.getElementById("confirmation").checked
        if ("value" in result) {
          if (answer) {
            this.deleteCommission(commission.id)
          }
        }
      })
    },

    async deleteCommission(commissionId) {
      try {
        this.loading = true
        await this.agentCommissionService.deleteAgentCommission(commissionId)
        this.alertNotify("success", this.$tc("phrases.agentCommissionDeleted"))
        await this.getAgentCommissions()
        this.loading = false
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
<style scoped></style>
