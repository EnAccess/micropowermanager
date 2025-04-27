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
          <span class="download-debts-span">
            You can download customers' outstanding debts from
            <a style="cursor: pointer" @click="exportDebts">here</a>
          </span>
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
      this.paginator
        .loadPage(pageNumber, this.searching ? { term: this.searchTerm } : {})
        .then((response) => {
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

.download-debts-span {
  float: right;
  margin-right: 1rem;
  min-height: 2rem;
  font-size: medium;
  font-weight: 500;
}
</style>
