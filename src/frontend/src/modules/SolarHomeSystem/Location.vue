<template>
  <widget
    :title="$tc('words.location')"
    color="primary"
    :subscriber="'shs-location'"
  >
    <client-map
      :mappingService="mappingService"
      ref="mapRef"
      :edit="false"
      :zoom="12"
    />
  </widget>
</template>

<script>
import { geoJsonToLatLon } from "@/Helpers/Utils.js"
import ClientMap from "@/modules/Map/ClientMap.vue"
import { MappingService, MARKER_TYPE } from "@/services/MappingService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Location",
  components: {
    Widget,
    ClientMap,
  },
  props: {
    device: {
      type: Object,
      required: true,
    },
    serialNumber: {
      type: String,
      required: true,
    },
    id: {
      type: [String, Number],
      required: true,
    },
  },
  data() {
    return {
      mappingService: new MappingService(),
    }
  },
  mounted() {
    this.setMapData()
    this.$emit("widget-loaded", "location")
  },
  methods: {
    setMapData() {
      const location = geoJsonToLatLon(this.device.geo)
      if (location == null) {
        return
      }
      const lat = location.lat
      const lon = location.lon

      const markingInfo = {
        id: this.id,
        name: this.serialNumber,
        serialNumber: this.serialNumber,
        lat: lat,
        lon: lon,
        deviceType: "solar_home_system",
        markerType: MARKER_TYPE.SHS,
      }

      this.mappingService.setCenter([lat, lon])
      this.mappingService.setMarkingInfos([markingInfo])

      this.$nextTick(() => {
        if (this.$refs.mapRef) {
          this.$refs.mapRef.setDeviceMarkers()
        }
      })
    },
  },
}
</script>
