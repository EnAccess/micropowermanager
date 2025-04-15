<template>
  <div>
    <AddEBikeModal
      :showAddEBike="showAddEBike"
      @hideAddEBike="
        () => {
          showAddEBike = false
        }
      "
      @created="updateList"
    />
    <EBikeDetailModal
      :e-bike="eBikeService.eBike"
      :showEBikeDetail="showEBikeDetail"
      @hideEBikeDetail="
        () => {
          showEBikeDetail = false
        }
      "
    />
    <widget
      :id="'eBike-list'"
      :title="$tc('words.e_bike', 2)"
      :paginator="eBikeService.paginator"
      :search="true"
      :subscriber="subscriber"
      :button="true"
      :button-text="$tc('phrases.newEBike')"
      :route_name="'/e-bikes'"
      color="green"
      @widgetAction="
        () => {
          showAddEBike = true
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
          style="cursor: pointer !important"
          v-for="eBike in eBikeService.list"
          :key="eBike.id"
          @click="detail(eBike.serialNumber)"
        >
          <md-table-cell>{{ eBike.serialNumber }}</md-table-cell>
          <md-table-cell>{{ eBike.manufacturer.name }}</md-table-cell>
          <md-table-cell>{{ eBike.appliance.name }}</md-table-cell>
          <md-table-cell v-if="eBike.device?.person">
            <router-link :to="`/people/${eBike.device.person.id}`">
              {{ `${eBike.device.person.name} ${eBike.device.person.surname}` }}
            </router-link>
          </md-table-cell>
          <md-table-cell v-else>-</md-table-cell>
          <md-table-cell>
            {{ timeForTimeZone(eBike.updatedAt) }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { notify, timing } from "@/mixins"
import { EventBus } from "@/shared/eventbus"
import Widget from "@/shared/Widget.vue"
import AddEBikeModal from "@/modules/EBikes/AddEBikeModal.vue"
import { EBikeService } from "@/services/EBikeService"
import EBikeDetailModal from "@/modules/EBikes/EBikeDetailModal.vue"

export default {
  name: "EBikes",
  mixins: [notify, timing],
  components: { EBikeDetailModal, AddEBikeModal, Widget },
  data() {
    return {
      eBikeService: new EBikeService(),
      subscriber: "eBikes",
      showAddEBike: false,
      showEBikeDetail: false,
      serialNumberQueryParam: null,
    }
  },
  created() {
    this.serialNumberQueryParam = this.$route.query.serialNumber
    if (this.serialNumberQueryParam) {
      this.detail(this.serialNumberQueryParam)
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
      this.eBikeService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.eBikeService.list.length,
      )
    },
    updateList(eBike) {
      this.showAddEBike = false
      const eBikeList = [...this.eBikeService.list]
      eBikeList.unshift(eBike)
      this.eBikeService.updateList(eBikeList)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.eBikeService.list.length,
      )
    },
    searching(searchTerm) {
      this.eBikeService.search(searchTerm)
    },
    endSearching() {
      this.eBikeService.showAll()
    },
    async detail(serialNumber) {
      try {
        await this.eBikeService.getEBikeBySerialNumber(serialNumber)
        this.showEBikeDetail = true
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped></style>
