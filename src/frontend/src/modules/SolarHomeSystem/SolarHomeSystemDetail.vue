<template>
  <div class="page-container">
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-50 md-small-size-100">
        <widget
          :title="$tc('words.details')"
          color="green"
          :subscriber="'shs-details'"
        >
          <md-list class="md-double-line">
            <md-list-item>
              <div class="md-list-item-text">
                <span>{{ $tc("phrases.serialNumber") }}</span>
                <span>{{ shs.serialNumber }}</span>
              </div>
            </md-list-item>
            <md-divider></md-divider>
            <md-list-item>
              <div class="md-list-item-text">
                <span>{{ $tc("words.manufacturer") }}</span>
                <span>{{ shs.manufacturer?.name || "-" }}</span>
              </div>
            </md-list-item>
            <md-divider></md-divider>
            <md-list-item>
              <div class="md-list-item-text">
                <span>{{ $tc("words.appliance") }}</span>
                <span>{{ shs.appliance?.name || "-" }}</span>
              </div>
            </md-list-item>
            <md-divider></md-divider>
            <md-list-item>
              <div class="md-list-item-text">
                <span>{{ $tc("phrases.lastUpdate") }}</span>
                <span>{{ timeForTimeZone(shs.updatedAt) }}</span>
              </div>
            </md-list-item>
          </md-list>
        </widget>
      </div>

      <div class="md-layout-item md-size-50 md-small-size-100">
        <widget
          :title="$tc('words.owner')"
          color="green"
          :subscriber="'shs-owner'"
          v-if="shs.device && shs.device.person"
        >
          <md-list class="md-double-line">
            <md-list-item :to="`/people/${shs.device.person.id}`">
              <div class="md-list-item-text">
                <span>{{ $tc("words.name") }}</span>
                <span>
                  {{ shs.device.person.name }} {{ shs.device.person.surname }}
                </span>
              </div>
              <md-icon>arrow_forward</md-icon>
            </md-list-item>
          </md-list>
        </widget>
      </div>
    </div>

    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-100">
        <widget
          :title="$tc('words.location')"
          color="green"
          :subscriber="'shs-location'"
          v-if="shs.device && shs.device.address"
        >
          <client-map
            :mappingService="mappingService"
            ref="shsMapRef"
            :edit="false"
            :zoom="12"
          />
        </widget>
      </div>
    </div>
  </div>
</template>

<script>
import Widget from "@/shared/widget"
import ClientMap from "@/modules/Map/ClientMap.vue"
import { MappingService, MARKER_TYPE } from "@/services/MappingService"
import { SolarHomeSystemService } from "@/services/SolarHomeSystemService"
import { timing, notify } from "@/mixins"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "SolarHomeSystemDetail",
  mixins: [timing, notify],
  components: {
    Widget,
    ClientMap,
  },
  data() {
    return {
      solarHomeSystemService: new SolarHomeSystemService(),
      shs: {},
      mappingService: new MappingService(),
    }
  },
  created() {
    const shsId = this.$route.params.id
    this.loadSolarHomeSystem(shsId)
  },
  methods: {
    async loadSolarHomeSystem(id) {
      try {
        this.shs = await this.solarHomeSystemService.getSolarHomeSystem(id)
        EventBus.$emit("widgetContentLoaded", "shs-details", 1)
        EventBus.$emit("widgetContentLoaded", "shs-owner", 1)

        if (this.shs.device && this.shs.device.address) {
          this.setMapData()
          EventBus.$emit("widgetContentLoaded", "shs-location", 1)
        }
      } catch (e) {
        this.alertNotify(
          "error",
          e.message || this.$tc("phrases.errorLoadingShs"),
        )
      }
    },
    setMapData() {
      if (this.shs.device.address.geo && this.shs.device.address.geo.points) {
        const points = this.shs.device.address.geo.points.split(",")
        if (points.length === 2) {
          const lat = parseFloat(points[0])
          const lon = parseFloat(points[1])

          const markingInfo = {
            id: this.shs.id,
            name: this.shs.serialNumber,
            serialNumber: this.shs.serialNumber,
            lat: lat,
            lon: lon,
            deviceType: "solar_home_system",
            markerType: MARKER_TYPE.SHS,
          }

          this.mappingService.setCenter([lat, lon])
          this.mappingService.setMarkingInfos([markingInfo])

          this.$nextTick(() => {
            if (this.$refs.shsMapRef) {
              this.$refs.shsMapRef.setDeviceMarkers()
            }
          })
        }
      }
    },
  },
}
</script>

<style scoped>
.page-container {
  padding: 16px;
}
</style>
