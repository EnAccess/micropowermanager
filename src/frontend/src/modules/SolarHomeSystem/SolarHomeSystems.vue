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
      color="green"
      @widgetAction="
        () => {
          showAddSolarHomeSystem = true
        }
      "
    >
      <md-table md-card style="margin-left: 0">
        <md-table-row>
          <md-table-head>
            {{ $tc("phrases.serialNumber") }}
          </md-table-head>
          <md-table-head>
            {{ $tc("words.manufacturer") }}
          </md-table-head>
          <md-table-head>{{ $tc("words.name") }}</md-table-head>
          <md-table-head>{{ $tc("words.owner") }}</md-table-head>
          <md-table-head>
            {{ $tc("phrases.lastUpdate") }}
          </md-table-head>
        </md-table-row>
        <md-table-row
          v-for="shs in solarHomeSystemService.list"
          :key="shs.id"
          @click="navigateToDetails(shs.id)"
          class="cursor-pointer"
        >
          <md-table-cell>{{ shs.serialNumber }}</md-table-cell>
          <md-table-cell>{{ shs.manufacturer.name }}</md-table-cell>
          <md-table-cell>{{ shs.appliance.name }}</md-table-cell>
          <md-table-cell v-if="shs.device?.person">
            <router-link :to="`/people/${shs.device.person.id}`">
              {{ `${shs.device.person.name} ${shs.device.person.surname}` }}
            </router-link>
          </md-table-cell>
          <md-table-cell v-else>-</md-table-cell>
          <md-table-cell>
            {{ timeForTimeZone(shs.updatedAt) }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { SolarHomeSystemService } from "@/services/SolarHomeSystemService"
import { timing } from "@/mixins"
import { EventBus } from "@/shared/eventbus"
import Widget from "@/shared/Widget.vue"
import AddSolarHomeSystemModal from "@/modules/SolarHomeSystem/AddSolarHomeSystemModal.vue"

export default {
  name: "SolarHomeSystems",
  mixins: [timing],
  components: { AddSolarHomeSystemModal, Widget },
  data() {
    return {
      solarHomeSystemService: new SolarHomeSystemService(),
      subscriber: "solarHomeSystems",
      showAddSolarHomeSystem: false,
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
    updateList(shs) {
      this.showAddSolarHomeSystem = false
      const shsList = [...this.solarHomeSystemService.list]
      shsList.unshift(shs)
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
  },
}
</script>

<style scoped></style>
