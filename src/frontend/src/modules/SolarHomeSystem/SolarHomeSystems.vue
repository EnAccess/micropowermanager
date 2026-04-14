<template>
  <div>
    <add-solar-home-system-modal
      :showAddShs="showAddSolarHomeSystem"
      @hideAddShs="
        () => {
          showAddSolarHomeSystem = false
        }
      "
      @created="updateList"
    />
    <widget
      :id="'shs-list'"
      :title="$tc('words.shs', 2)"
      :paginator="solarHomeSystemService.paginator"
      :search="true"
      :subscriber="subscriber"
      :button="true"
      :button-text="$tc('phrases.newShs')"
      :route_name="'/solar-home-systems'"
      :show_per_page="true"
      color="primary"
      @widgetAction="
        () => {
          showAddSolarHomeSystem = true
        }
      "
    >
      <md-table
        v-model="solarHomeSystemService.list"
        md-card
        style="margin-left: 0"
        md-sort="updated_at"
        md-sort-order="desc"
        @md-sorted="onSort"
      >
        <md-table-row
          slot="md-table-row"
          slot-scope="{ item }"
          @click="navigateToDetails(item.id)"
          class="cursor-pointer"
        >
          <md-table-cell
            :md-label="$tc('phrases.serialNumber')"
            md-sort-by="serial_number"
          >
            {{ item.serialNumber }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.manufacturer')">
            {{ item.manufacturer ? item.manufacturer.name : "-" }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.name')">
            {{ item.appliance ? item.appliance.name : "-" }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.owner')" md-sort-by="owner">
            <template v-if="item.device && item.device.person">
              <router-link :to="`/people/${item.device.person.id}`">
                {{ `${item.device.person.name} ${item.device.person.surname}` }}
              </router-link>
            </template>
            <template v-else>-</template>
          </md-table-cell>
          <md-table-cell
            :md-label="$tc('phrases.lastUpdate')"
            md-sort-by="updated_at"
          >
            {{ timeForTimeZone(item.updatedAt) }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { timing } from "@/mixins/timing.js"
import AddSolarHomeSystemModal from "@/modules/SolarHomeSystem/AddSolarHomeSystemModal.vue"
import { SolarHomeSystemService } from "@/services/SolarHomeSystemService.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "SolarHomeSystems",
  mixins: [timing],
  components: { AddSolarHomeSystemModal, Widget },
  data() {
    return {
      solarHomeSystemService: new SolarHomeSystemService(),
      subscriber: "solarHomeSystems",
      showAddSolarHomeSystem: false,
      currentSortBy: "updated_at",
      currentSortOrder: "desc",
    }
  },
  mounted() {
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
      this.solarHomeSystemService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.solarHomeSystemService.list.length,
      )
    },
    updateList(createdShs) {
      this.showAddSolarHomeSystem = false
      const newlyCreatedSolarHomeSystems = Array.isArray(createdShs)
        ? createdShs
        : [createdShs]

      const shsList = [
        ...newlyCreatedSolarHomeSystems,
        ...this.solarHomeSystemService.list,
      ]
      this.solarHomeSystemService.updateList(shsList)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.solarHomeSystemService.list.length,
      )
    },
    searching(searchTerm) {
      this.solarHomeSystemService.search(searchTerm)
    },
    navigateToDetails(id) {
      this.$router.push(`/solar-home-systems/${id}`)
    },
    endSearching() {
      this.solarHomeSystemService.showAll()
    },
    onSort(field) {
      if (!field) return

      if (this.currentSortBy === field) {
        this.currentSortOrder =
          this.currentSortOrder === "desc" ? "asc" : "desc"
      } else {
        this.currentSortBy = field
        this.currentSortOrder = "asc"
      }

      const prefix = this.currentSortOrder === "desc" ? "-" : ""
      const term = {
        page: 1,
        per_page: this.$route.query.per_page || 15,
        sort_by: `${prefix}${this.currentSortBy}`,
      }

      EventBus.$emit("loadPage", this.solarHomeSystemService.paginator, term)
    },
  },
}
</script>

<style scoped lang="scss"></style>
