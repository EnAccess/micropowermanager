<template>
  <div>
    <widget
      :id="'client-list-widget'"
      :title="$tc('phrases.customerList')"
      :search="true"
      :subscriber="subscriber"
      :button="true"
      :paginator="people.paginator"
      :route_name="'/people'"
      color="green"
      :button-text="$tc('phrases.addCustomer')"
      @widgetAction="
        () => {
          showAddClient = true
          key++
        }
      "
    >
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <div class="export-buttons-container">
            <div class="filter-section">
              <md-field>
                <label>{{ $tc("words.agent") }}</label>
                <md-select v-model="selectedAgentId" @change="filterByAgent">
                  <md-option :value="null">{{ $tc("words.all") }}</md-option>
                  <md-option
                    v-for="agent in agentService.list"
                    :key="agent.id"
                    :value="agent.id"
                  >
                    {{
                      agent.person
                        ? `${agent.person.name} ${agent.person.surname}`
                        : agent.name
                    }}
                  </md-option>
                </md-select>
              </md-field>
            </div>
            <div class="export-section">
              <span class="download-debts-span">
                You can download customers' outstanding debts from
                <a style="cursor: pointer" @click="exportDebts">here</a>
              </span>
              <md-button
                class="md-raised md-primary export-csv-button"
                @click="showExportModal = true"
              >
                <md-icon>download</md-icon>
                {{ $tc("phrases.exportCustomers") }}
              </md-button>
            </div>
          </div>
        </div>
        <div class="md-layout-item md-size-100">
          <md-table md-card style="margin-left: 0">
            <md-table-row>
              <md-table-head>
                {{ $tc("words.name") }}
              </md-table-head>
              <md-table-head>
                {{ $tc("words.phone") }}
              </md-table-head>
              <md-table-head>
                {{ $tc("words.city") }}
              </md-table-head>
              <md-table-head>
                {{ $tc("words.isActive") }}
              </md-table-head>
              <md-table-head>
                {{ $tc("words.device") }}
              </md-table-head>
              <md-table-head>
                {{ $tc("words.agent") }}
              </md-table-head>
              <md-table-head>
                {{ $tc("phrases.lastUpdate") }}
              </md-table-head>
            </md-table-row>
            <md-table-row
              v-for="client in people.list"
              :key="client.id"
              @click="detail(client.id)"
              style="cursor: pointer"
            >
              <md-table-cell>
                {{ client.name }} {{ client.surname }}
              </md-table-cell>
              <md-table-cell v-if="client.addresses.length > 0">
                {{ client.addresses[0].phone }}
              </md-table-cell>
              <md-table-cell
                class="hidden-xs"
                v-if="client.addresses.length > 0"
              >
                {{
                  client.addresses[0].city ? client.addresses[0].city.name : "-"
                }}
              </md-table-cell>
              <md-table-cell>
                {{ client.is_active ? $tc("words.yes") : $tc("words.no") }}
              </md-table-cell>
              <md-table-cell v-if="client.devices.length > 0">
                {{ deviceList(client.devices) }}
              </md-table-cell>
              <md-table-cell v-if="client.devices.length === 0">
                -
              </md-table-cell>
              <md-table-cell>
                {{ getAgentName(client) }}
              </md-table-cell>
              <md-table-cell class="hidden-xs">
                {{ timeForTimeZone(client.lastUpdate) }}
              </md-table-cell>
            </md-table-row>
          </md-table>
        </div>
      </div>
    </widget>

    <add-client-modal
      :showAddClient="showAddClient"
      @hideAddCustomer="() => (showAddClient = false)"
      :key="key"
    />

    <!-- Export Modal -->
    <md-dialog :md-active.sync="showExportModal" class="export-dialog">
      <md-dialog-title>{{ $tc("phrases.exportCustomers") }}</md-dialog-title>

      <md-dialog-content>
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-50">
            <md-field>
              <label>{{ $tc("words.currency") }}</label>
              <md-select v-model="exportFilters.currency">
                <md-option value="TSZ">TSZ</md-option>
                <md-option value="USD">USD</md-option>
                <md-option value="EUR">EUR</md-option>
                <md-option value="NGN">NGN</md-option>
                <md-option value="FCFA">FCFA</md-option>
              </md-select>
            </md-field>
          </div>
          <div class="md-layout-item md-size-50">
            <md-field>
              <label>{{ $tc("words.timeZone") }}</label>
              <md-select v-model="exportFilters.timeZone">
                <md-option value="UTC">UTC</md-option>
                <md-option value="Africa/Lagos">Africa/Lagos</md-option>
                <md-option value="Africa/Douala">Africa/Douala</md-option>
                <md-option value="Africa/Dar_es_Salaam">
                  Africa/Dar_es_Salaam
                </md-option>
                <md-option value="Europe/Berlin">Europe/Berlin</md-option>
              </md-select>
            </md-field>
          </div>
        </div>

        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-33">
            <md-field>
              <label>{{ $tc("words.status") }}</label>
              <md-select v-model="exportFilters.isActive">
                <md-option :value="null">{{ $tc("words.all") }}</md-option>
                <md-option :value="true">{{ $tc("words.active") }}</md-option>
                <md-option :value="false">
                  {{ $tc("words.inactive") }}
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div class="md-layout-item md-size-33">
            <md-field>
              <label>{{ $tc("words.deviceType") }}</label>
              <md-select v-model="exportFilters.deviceType">
                <md-option :value="null">{{ $tc("words.all") }}</md-option>
                <md-option value="meter">{{ $tc("words.meter") }}</md-option>
                <md-option value="appliance">
                  {{ $tc("words.appliance") }}
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div class="md-layout-item md-size-33">
            <md-field>
              <label>{{ $tc("words.format") }}</label>
              <md-select v-model="exportFilters.format">
                <md-option value="csv">CSV</md-option>
                <md-option value="xlsx">Excel</md-option>
              </md-select>
            </md-field>
          </div>
        </div>
      </md-dialog-content>

      <md-dialog-actions>
        <md-button @click="showExportModal = false">
          {{ $tc("words.cancel") }}
        </md-button>
        <md-button class="md-primary" @click="exportCustomers">
          {{ $tc("words.export") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { resources } from "@/resources"
import { Paginator } from "@/Helpers/Paginator"
import { EventBus } from "@/shared/eventbus"
import Widget from "@/shared/Widget.vue"
import { People } from "@/services/PersonService"
import { timing } from "@/mixins/timing"
import { notify } from "@/mixins/notify"
import i18n from "../../i18n"
import AddClientModal from "@/modules/Client/AddClientModal.vue"
import { OutstandingDebtsExportService } from "@/services/OutstandingDebtsExportService"
import { CustomerExportService } from "@/services/CustomerExportService"
import { MainSettingsService } from "@/services/MainSettingsService"
import { AgentService } from "@/services/AgentService"

const debounce = require("debounce")

export default {
  name: "Clients",
  mixins: [timing, notify],
  components: { AddClientModal, Widget },
  data() {
    return {
      subscriber: "client.list",
      people: new People(),
      clientList: null,
      tmpClientList: null,
      paginator: new Paginator(resources.person.list),
      searchTerm: "",
      currentFrom: 0,
      currentTo: 0,
      total: 0,
      currentPage: 0,
      totalPages: 0,
      showAddClient: false,
      key: 0,
      outstandingDebtsExportService: new OutstandingDebtsExportService(),
      customerExportService: new CustomerExportService(),
      mainSettingsService: new MainSettingsService(),
      agentService: new AgentService(),
      showExportModal: false,
      selectedAgentId: null,
      exportFilters: {
        format: "csv",
        currency: "TSZ",
        timeZone: "UTC",
        isActive: null,
        city: null,
        deviceType: null,
      },
    }
  },
  watch: {
    searchTerm: debounce(function () {
      if (this.searchTerm.length > 0) {
        this.doSearch(this.searchTerm)
      } else {
        this.showAllEntries()
      }
    }, 1000),
  },

  mounted() {
    this.getClientList()
    this.loadMainSettings()
    this.loadAgents()
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.searching)
    EventBus.$on("end_searching", this.endSearching)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
    EventBus.$off("searching", this.searching)
    EventBus.$off("end_searching", this.endSearching)
  },

  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.people.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.people.list.length,
      )
    },
    searching(searchTerm) {
      this.people.search(searchTerm)
    },
    endSearching() {
      this.people.showAll()
    },
    detail(id) {
      this.$router.push({ path: "/people/" + id })
    },
    getClientList(pageNumber = 1) {
      const params = this.searching ? { term: this.searchTerm } : {}
      if (this.selectedAgentId) {
        params.agentId = this.selectedAgentId
      }

      this.paginator.loadPage(pageNumber, params).then((response) => {
        this.tmpClientList = this.clientList = response.data
      })
    },
    deviceList(devices) {
      return devices.reduce((acc, curr, index, arr) => {
        if (index !== arr.length - 1) {
          acc +=
            curr.device_serial + ` (${i18n.tc(`words.${curr.device_type}`)}),`
        } else {
          acc +=
            curr.device_serial + ` (${i18n.tc(`words.${curr.device_type}`)})`
        }
        return acc
      }, "")
    },

    doSearch(searchTerm) {
      this.searching = true

      this.paginator = new Paginator(resources.person.search)

      this.paginator.loadPage(1, { term: searchTerm }).then((response) => {
        this.clientList = response.data
      })
    },
    showAllEntries() {
      this.searchTerm = ""
      this.paginator = new Paginator(resources.person.list)
      this.searching = false
      this.currentPage = 0
      this.getClientList()
    },
    clearSearch() {
      this.searchTerm = ""
    },
    async exportDebts() {
      try {
        const response =
          await this.outstandingDebtsExportService.exportOutstandingDebts()
        const blob = new Blob([response.data])
        const downloadUrl = window.URL.createObjectURL(blob)
        const a = document.createElement("a")
        a.href = downloadUrl
        const contentDisposition = response.headers["content-disposition"]
        const fileNameMatch = contentDisposition?.match(/filename="(.+)"/)
        a.download = fileNameMatch
          ? fileNameMatch[1]
          : "export_customers_debts.xlsx"
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(downloadUrl)
      } catch (e) {
        this.alertNotify(
          "error",
          "Error occured while exporting Customers' debts",
        )
      }
    },
    async loadMainSettings() {
      try {
        const settings = await this.mainSettingsService.list()
        this.exportFilters.currency = settings.currency || "TSZ"
        this.exportFilters.timeZone = "UTC" // Default timezone
      } catch (e) {
        console.error("Failed to load main settings:", e)
      }
    },
    async loadAgents() {
      try {
        const response = await this.agentService.repository.list()
        this.agentService.list = response.data.data || response.data
      } catch (e) {
        console.error("Failed to load agents:", e)
      }
    },
    async exportCustomers() {
      try {
        const data = {
          format: this.exportFilters.format,
          currency: this.exportFilters.currency,
          timeZone: this.exportFilters.timeZone,
        }

        // Add optional filters if they are set
        if (this.exportFilters.isActive !== null) {
          data.isActive = this.exportFilters.isActive
        }
        if (this.exportFilters.city) {
          data.city = this.exportFilters.city
        }
        if (this.exportFilters.deviceType) {
          data.deviceType = this.exportFilters.deviceType
        }
        if (this.selectedAgentId) {
          data.agent = this.selectedAgentId
        }

        const response = await this.customerExportService.exportCustomers(data)
        const blob = new Blob([response.data])
        const downloadUrl = window.URL.createObjectURL(blob)
        const a = document.createElement("a")
        a.href = downloadUrl
        const contentDisposition = response.headers["content-disposition"]
        const fileNameMatch = contentDisposition?.match(/filename="(.+)"/)
        a.download = fileNameMatch ? fileNameMatch[1] : "export_customers.csv"
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(downloadUrl)
        this.alertNotify("success", "Customers exported successfully!")
        this.showExportModal = false
      } catch (e) {
        this.alertNotify("error", "Error occurred while exporting customers")
      }
    },
    filterByAgent() {
      this.getClientList()
    },
    getAgentName(client) {
      if (
        client.agent_sold_appliance &&
        client.agent_sold_appliance.assigned_appliance &&
        client.agent_sold_appliance.assigned_appliance.agent
      ) {
        const agent = client.agent_sold_appliance.assigned_appliance.agent
        return agent.person
          ? `${agent.person.name} ${agent.person.surname}`
          : agent.name || "-"
      }
      return "-"
    },
  },
}
</script>

<style lang="scss" scoped>
.md-app {
  min-height: 100vh;
  border: 1px solid rgba(#000, 0.12);
}

// Demo purposes only
.md-drawer {
  width: 230px;
  max-width: calc(100vw - 125px);
}

.export-buttons-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0 1rem;
}

.filter-section {
  flex: 1;
  max-width: 300px;
}

.export-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.download-debts-span {
  font-size: medium;
  font-weight: 500;
}

.export-csv-button {
  margin-left: auto;
}

.export-dialog {
  min-width: 600px;
}

.export-dialog .md-dialog-content {
  padding: 20px;
}
</style>
