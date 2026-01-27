<template>
  <div>
    <widget
      :id="'client-list-widget'"
      :title="$tc('phrases.customerList')"
      :search="true"
      :subscriber="subscriber"
      :button="true"
      :paginator="paginator"
      :route_name="'/people'"
      color="primary"
      :button-text="$tc('phrases.addCustomer')"
      :key="widgetKey"
      @widgetAction="
        () => {
          showAddClient = true
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
              <md-button
                class="md-raised md-default export-csv-button"
                @click="exportDebts"
                :disabled="downloading"
              >
                <md-icon class="export-icon">
                  <Transition mode="out-in" name="fade">
                    <md-progress-spinner
                      v-if="downloading"
                      md-mode="indeterminate"
                      :md-diameter="21"
                      :md-stroke="3"
                    />
                    <span v-else>download</span>
                  </Transition>
                </md-icon>
                {{ $tc("phrases.exportCustomersDebts") }}
              </md-button>
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
          <md-table
            v-model="people.list"
            md-card
            style="margin-left: 0"
            md-sort="created_at"
            md-sort-order="desc"
            @md-sorted="onSort"
          >
            <md-table-row
              slot="md-table-row"
              slot-scope="{ item }"
              @click="detail(item.id)"
              style="cursor: pointer"
            >
              <md-table-cell :md-label="$tc('words.name')" md-sort-by="name">
                {{ item.name }} {{ item.surname }}
              </md-table-cell>

              <md-table-cell :md-label="$tc('words.phone')">
                {{ item.addresses.length > 0 ? item.addresses[0].phone : "-" }}
              </md-table-cell>

              <md-table-cell
                :md-label="$tc('words.city')"
                md-sort-by="city"
                class="hidden-xs"
              >
                {{
                  item.addresses.length > 0 && item.addresses[0].city
                    ? item.addresses[0].city.name
                    : "-"
                }}
              </md-table-cell>

              <md-table-cell :md-label="$tc('words.isActive')">
                {{ item.is_active ? $tc("words.yes") : $tc("words.no") }}
              </md-table-cell>

              <md-table-cell :md-label="$tc('words.device')">
                {{ item.devices.length > 0 ? deviceList(item.devices) : "-" }}
              </md-table-cell>

              <md-table-cell :md-label="$tc('words.agent')" md-sort-by="agent">
                {{ getAgentName(item) }}
              </md-table-cell>

              <md-table-cell
                :md-label="$tc('phrases.lastUpdate')"
                md-sort-by="created_at"
                class="hidden-xs"
              >
                {{ timeForTimeZone(item.lastUpdate) }}
              </md-table-cell>
            </md-table-row>
          </md-table>
        </div>
      </div>
    </widget>

    <add-client-modal
      :showAddClient="showAddClient"
      @hideAddCustomer="() => (showAddClient = false)"
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
                <md-option value="">{{ $tc("words.all") }}</md-option>
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
                <md-option value="">{{ $tc("words.all") }}</md-option>
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
                <md-option value="">{{ $tc("words.all") }}</md-option>
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
                <md-option value="">{{ $tc("words.all") }}</md-option>
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

      <md-progress-bar md-mode="indeterminate" v-if="downloading" />

      <md-dialog-actions>
        <md-button class="md-raised" @click="showExportModal = false">
          {{ $tc("words.cancel") }}
        </md-button>
        <md-button
          class="md-primary md-raised"
          :disabled="downloading"
          @click="exportCustomers"
        >
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
      paginator: new Paginator(resources.person.list),
      searchTerm: "",
      showAddClient: false,
      outstandingDebtsExportService: new OutstandingDebtsExportService(),
      customerExportService: new CustomerExportService(),
      mainSettingsService: new MainSettingsService(),
      agentService: new AgentService(),
      showExportModal: false,
      selectedAgentId: null,
      widgetKey: 0,
      showFilter: false,
      currentSortBy: "created_at",
      currentSortOrder: "desc",
      exportFilters: {
        format: "csv",
        isActive: "",
        miniGrid: "",
        village: "",
        deviceType: "",
      },
      miniGridService: new MiniGridService(),
      cityService: new CityService(),
      isSearching: false,
      downloading: false,
      activeRequest: null,
    }
  },
  watch: {
    searchTerm: debounce(function () {
      this.handleSearch()
    }, 300),
  },

  mounted() {
    this.getClientList()

    // Load supporting data asynchronously without blocking UI
    this.loadSupportingData()

    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.onSearchEvent)
    EventBus.$on("end_searching", this.onEndSearchEvent)
  },

  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
    EventBus.$off("searching", this.onSearchEvent)
    EventBus.$off("end_searching", this.onEndSearchEvent)

    // Cancel any pending request
    if (this.activeRequest) {
      this.activeRequest.cancel()
    }
  },

  methods: {
    handleSearch() {
      if (this.searchTerm.length > 2) {
        this.performSearch()
      }
    },

    onSort(sortData) {
      if (typeof sortData === "string") {
        this.currentSortBy = sortData
        if (
          this.currentSortBy === sortData &&
          this.currentSortOrder === "asc"
        ) {
          this.currentSortOrder = "desc"
        } else {
          this.currentSortOrder = "asc"
        }
      } else if (sortData && typeof sortData === "object") {
        this.currentSortBy = sortData.name || null
        this.currentSortOrder = sortData.type || "desc"
      } else {
        this.currentSortBy = null
        this.currentSortOrder = "desc"
      }

      // Build term object with sort parameters for pagination
      const term = {}
      if (this.currentSortBy) {
        const prefix = this.currentSortOrder === "desc" ? "-" : ""
        term.sort_by = `${prefix}${this.currentSortBy}`
      }
      if (this.selectedAgentId) {
        term.agent_id = this.selectedAgentId
      }

      // Emit EventBus event so Paginate.vue includes sort params in all subsequent pagination calls
      EventBus.$emit("loadPage", this.paginator, term)

      // Load the first page with sort applied
      this.getClientList(1)
    },

    async performSearch() {
      // Cancel previous request if still pending
      if (this.activeRequest) {
        this.activeRequest.cancel()
      }

      this.isSearching = true

      try {
        // Create search paginator
        const searchPaginator = new Paginator(resources.person.search)

        const params = { term: this.searchTerm }
        if (this.currentSortBy) {
          const prefix = this.currentSortOrder === "desc" ? "-" : ""
          params.sort_by = `${prefix}${this.currentSortBy}`
        }
        if (this.selectedAgentId) {
          params.agent_id = this.selectedAgentId
        }

        const response = await searchPaginator.loadPage(1, params)

        // Update people list with search results
        this.people.updateList(response.data)
        this.paginator = searchPaginator

        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.people.list.length,
        )
      } catch (error) {
        if (error.message !== "Request cancelled") {
          console.error("Search error:", error)
        }
      } finally {
        this.isSearching = false
      }
    },

    onSearchEvent(searchTerm) {
      this.searchTerm = searchTerm
    },

    onEndSearchEvent() {
      this.searchTerm = ""
      this.showAllEntries()
    },

    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      // Always update with the returned data - the paginator already applied
      // sort and search parameters in the API request, so the data is correct
      this.people.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.people.list.length,
      )
    },

    detail(id) {
      this.$router.push({ path: "/people/" + id })
    },

    async getClientList(pageNumber = 1) {
      const params = {}
      if (this.currentSortBy) {
        const prefix = this.currentSortOrder === "desc" ? "-" : ""
        params.sort_by = `${prefix}${this.currentSortBy}`
      }

      if (this.isSearching && this.searchTerm) {
        params.term = this.searchTerm
      }

      if (this.selectedAgentId) {
        params.agent_id = this.selectedAgentId
      }

      try {
        const response = await this.paginator.loadPage(pageNumber, params)
        this.people.updateList(response.data)

        // Keep widget content state in sync with the actual list length.
        // This ensures that after clearing a search (especially one that returned no results),
        // the customer list becomes visible again instead of staying empty while
        // the paginator still shows existing entries.
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.people.list.length,
        )

        if (this.selectedAgentId) {
          // Update pagination for filtered results
          this.updateFilteredPagination(response.data.length)
        }
      } catch (error) {
        console.error("Error loading client list:", error)
      }
    },

    updateFilteredPagination(count) {
      const filteredPaginator = new Paginator(resources.person.list)
      filteredPaginator.totalEntries = count
      filteredPaginator.currentPage = 1
      filteredPaginator.perPage = count
      filteredPaginator.totalPage = 1
      filteredPaginator.from = 1
      filteredPaginator.to = count

      this.$set(this.people, "paginator", filteredPaginator)
      this.widgetKey++
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

    showAllEntries() {
      this.searchTerm = ""
      this.paginator = new Paginator(resources.person.list)
      this.isSearching = false
      this.getClientList()
    },

    async exportDebts() {
      this.downloading = true

      try {
        const response =
          await this.outstandingDebtsExportService.exportOutstandingDebts()

        const blob = new Blob([response.data])
        const downloadUrl = window.URL.createObjectURL(blob)
        const a = document.createElement("a")
        a.href = downloadUrl

        const contentDisposition = response.headers["content-disposition"]
        const filename =
          contentDisposition?.split("filename=")[1]?.replace(/['"]/g, "") ??
          "export.xlsx"

        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(downloadUrl)
      } catch (e) {
        this.alertNotify(
          "error",
          "Error occured while exporting Customers' debts",
        )
      } finally {
        this.downloading = false
      }
    },

    async loadSupportingData() {
      // Run all these in parallel without blocking
      await Promise.allSettled([
        this.loadMainSettings(),
        this.loadAgents(),
        this.loadMiniGrids(),
        this.loadCities(),
      ])
    },

    async loadMainSettings() {
      try {
        const settings = await this.mainSettingsService.list()
        this.exportFilters.currency = settings.currency || "TZS"
        this.exportFilters.timeZone = "UTC"
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
        await this.miniGridService.getMiniGrids()
      } catch (error) {
        console.error("Failed to load mini grids:", error)
      }
    },

    async loadCities() {
      try {
        await this.cityService.getCities()
      } catch (error) {
        console.error("Failed to load cities:", error)
      }
    },

    async exportCustomers() {
      this.downloading = true

      try {
        const data = {
          format: this.exportFilters.format,
        }

        if (this.exportFilters.isActive !== "") {
          data.isActive = this.exportFilters.isActive
        }
        if (this.exportFilters.miniGrid !== "") {
          data.miniGrid = this.exportFilters.miniGrid
        }
        if (this.exportFilters.village !== "") {
          data.village = this.exportFilters.village
        }
        if (this.exportFilters.deviceType !== "") {
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
        const filename =
          contentDisposition?.split("filename=")[1]?.replace(/['"]/g, "") ??
          (this.exportFilters.format === "excel" ? "export.xlsx" : "export.csv")

        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(downloadUrl)
        this.alertNotify("success", "Customers exported successfully!")
        this.showExportModal = false
      } catch (e) {
        this.alertNotify("error", "Error occurred while exporting customers")
      } finally {
        this.downloading = false
      }
    },

    filterByAgent() {
      this.paginator = new Paginator(resources.person.list)
      this.paginator.currentPage = 1
      this.getClientList(1)
      this.widgetKey++
    },

    getAgentName(client) {
      if (
        client.agent_sold_appliance &&
        client.agent_sold_appliance.assigned_appliance &&
        client.agent_sold_appliance.assigned_appliance.agent
      ) {
        const agentId = client.agent_sold_appliance.assigned_appliance.agent.id
        const agent = this.agentService.list.find((a) => a.id === agentId)
        if (agent) {
          return agent.person
            ? `${agent.person.name} ${agent.person.surname}`
            : agent.email || "-"
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

.fade-enter-active .fade-leave-active {
  transition: opacity ease;
}
</style>
