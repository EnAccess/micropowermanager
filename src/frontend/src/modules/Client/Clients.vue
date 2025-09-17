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
      :key="widgetKey"
      @widgetAction="
        () => {
          showAddClient = true
          this.key++
        }
      "
    >
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <div class="export-buttons-container">
            <div class="filter-section">
              <md-button
                class="md-dense md-button-icon"
                @click="showFilter = !showFilter"
              >
                {{ $tc("words.filter") }}
                <md-icon>
                  {{
                    showFilter ? "keyboard_arrow_down" : "keyboard_arrow_left"
                  }}
                </md-icon>
              </md-button>
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
                {{ $tc("phrases.exportAllCustomers") }}
              </md-button>
            </div>
          </div>
        </div>

        <!-- Collapsible Filter Section -->
        <div class="md-layout-item md-size-100" v-if="showFilter">
          <div class="filter-expanded-section">
            <md-field>
              <label>
                {{ $tc("words.agent") }} ({{ agentService.list.length }}
                agents loaded)
              </label>
              <md-select v-model="selectedAgentId" @input="filterByAgent">
                <md-option :value="null">{{ $tc("words.all") }}</md-option>
                <md-option
                  v-for="agent in agentService.list"
                  :key="agent.id"
                  :value="agent.id"
                >
                  {{
                    agent.person
                      ? `${agent.person.name} ${agent.person.surname}`
                      : agent.email
                  }}
                </md-option>
              </md-select>
            </md-field>

            <!-- Debug info -->
            <div
              v-if="agentService.list.length === 0"
              style="color: red; font-size: 12px"
            >
              No agents loaded. Check console for errors.
            </div>
          </div>
        </div>

        <!-- No customers message for agent filter -->
        <div
          class="md-layout-item md-size-100"
          v-if="selectedAgentId && people.list.length === 0"
        >
          <div
            style="
              text-align: center;
              padding: 2rem;
              background: #f5f5f5;
              border-radius: 4px;
              margin: 1rem 0;
            "
          >
            <md-icon style="font-size: 48px; color: #ccc; margin-bottom: 1rem">
              people
            </md-icon>
            <div style="color: #666; font-size: 18px; margin-bottom: 0.5rem">
              No customers found for this agent
            </div>
            <div style="color: #999; font-size: 14px">
              Try selecting a different agent or clear the filter
            </div>
          </div>
        </div>

        <div class="md-layout-item md-size-100" v-if="people.list.length > 0">
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
            <!-- No customers found message -->
            <md-table-row v-if="people.list.length === 0">
              <md-table-cell
                colspan="7"
                style="text-align: center; padding: 2rem"
              >
                <div style="color: #666; font-style: italic">
                  No customers found
                </div>
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

    <!-- Updated Export Modal for Customer Export -->
    <md-dialog :md-active.sync="showExportModal" class="export-dialog">
      <md-dialog-title>{{ $tc("phrases.exportCustomers") }}</md-dialog-title>

      <md-dialog-content>
        <!-- Geographical Filters Row -->
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-50">
            <md-field>
              <label>{{ $tc("words.miniGrid") }}</label>
              <md-select v-model="exportFilters.miniGrid">
                <md-option :value="null">{{ $tc("words.all") }}</md-option>
                <md-option
                  v-for="miniGrid in miniGridService.list"
                  :key="miniGrid.id"
                  :value="miniGrid.id"
                >
                  {{ miniGrid.name }}
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div class="md-layout-item md-size-50">
            <md-field>
              <label>{{ $tc("words.village") }}</label>
              <md-select v-model="exportFilters.village">
                <md-option :value="null">{{ $tc("words.all") }}</md-option>
                <md-option
                  v-for="city in cityService.list"
                  :key="city.id"
                  :value="city.id"
                >
                  {{ city.name }}
                </md-option>
              </md-select>
            </md-field>
          </div>
        </div>

        <!-- Status, Device Type, and Format Row -->
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
                <md-option value="excel">Excel</md-option>
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
import { MiniGridService } from "@/services/MiniGridService"
import { CityService } from "@/services/CityService"

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
      widgetKey: 0,
      showFilter: false,
      exportFilters: {
        format: "csv",
        isActive: null,
        miniGrid: null,
        village: null,
        deviceType: null,
      },
      miniGridService: new MiniGridService(),
      cityService: new CityService(),
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
    this.loadMiniGrids()
    this.loadCities()
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
        params.agent_id = this.selectedAgentId
      }

      this.paginator
        .loadPage(pageNumber, params)
        .then((response) => {
          this.people.updateList(response.data)

          // Update pagination data when filtering by agent
          if (this.selectedAgentId) {
            // For filtered results, update pagination to reflect actual filtered count
            this.paginator.totalEntries = response.data.length
            this.paginator.currentPage = 1
            this.paginator.perPage = response.data.length
            this.paginator.totalPage = 1
            this.paginator.from = 1
            this.paginator.to = response.data.length

            // Create a proper Paginator instance for the people to force Widget re-render
            const filteredPaginator = new Paginator(resources.person.list)
            filteredPaginator.totalEntries = response.data.length
            filteredPaginator.currentPage = 1
            filteredPaginator.perPage = response.data.length
            filteredPaginator.totalPage = 1
            filteredPaginator.from = 1
            filteredPaginator.to = response.data.length

            // Force the paginator to be reactive
            this.$set(this.people, "paginator", filteredPaginator)

            // Force a re-render by updating the key
            this.key++
          }
        })
        .catch((error) => {
          console.error("Error loading client list:", error)
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
        const agents = response.data.data || response.data
        this.agentService.updateList(agents)
      } catch (e) {
        console.error("Failed to load agents:", e)
      }
    },
    async loadMiniGrids() {
      try {
        const response = await this.miniGridService.repository.list()
        const miniGrids = response.data.data || response.data
        this.miniGridService.updateList(miniGrids)
      } catch (e) {
        console.error("Failed to load mini grids:", e)
      }
    },
    async loadCities() {
      try {
        const response = await this.cityService.repository.list()
        const cities = response.data.data || response.data
        this.cityService.updateList(cities)
      } catch (e) {
        console.error("Failed to load cities:", e)
      }
    },
    async exportCustomers() {
      try {
        const data = {
          format: this.exportFilters.format,
        }

        // Add optional filters if they are set
        if (this.exportFilters.isActive !== null) {
          data.isActive = this.exportFilters.isActive
        }
        if (this.exportFilters.miniGrid) {
          data.miniGrid = this.exportFilters.miniGrid
        }
        if (this.exportFilters.village) {
          data.village = this.exportFilters.village
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

        // Fix Excel format - use correct file extension
        const defaultFileName =
          this.exportFilters.format === "excel"
            ? "export_customers.xlsx"
            : "export_customers.csv"
        a.download = fileNameMatch ? fileNameMatch[1] : defaultFileName

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
      if (this.selectedAgentId) {
        // Reset pagination when filtering
        this.paginator.currentPage = 1
        this.getClientList(1)
      } else {
        // When clearing filter, restore normal pagination
        this.paginator = new Paginator(resources.person.list)
        // Also reset the people paginator to restore normal pagination display
        this.people.paginator = this.paginator
        this.getClientList(1)
      }
      // Force widget re-render to update pagination display
      this.widgetKey++
    },
    getAgentName(client) {
      if (
        client.agent_sold_appliance &&
        client.agent_sold_appliance.assigned_appliance &&
        client.agent_sold_appliance.assigned_appliance.agent
      ) {
        const agentId = client.agent_sold_appliance.assigned_appliance.agent.id
        // Find the agent in our loaded agent list
        const agent = this.agentService.list.find((a) => a.id === agentId)
        if (agent) {
          const agentName = agent.person
            ? `${agent.person.name} ${agent.person.surname}`
            : agent.email || "-"
          return agentName
        }
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

.filter-expanded-section {
  background-color: #f5f5f5;
  padding: 1rem;
  border-radius: 4px;
  margin-top: 1rem;
  border: 1px solid #e0e0e0;
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
