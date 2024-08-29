<template>
  <div id="map"></div>
</template>

<script>
import { ICON_OPTIONS, ICONS, MARKER_TYPE } from "@/services/MappingService"
import { notify, sharedMap } from "@/mixins"

export default {
  name: "ClientMap",
  mixins: [notify, sharedMap],
  mounted() {
    this.map.on("draw:edited", (event) => {
      const editedItems = []
      const editedLayers = event.layers
      editedLayers.eachLayer((layer) => {
        const geoDataItem = {
          serialNumber: layer._tooltip._content,
          lat: layer._latlng.lat,
          lon: layer._latlng.lng,
        }
        editedItems.push(geoDataItem)
      })

      if (editedItems.length) {
        this.$emit("locationEdited", editedItems)
      }
    })
  },
  methods: {
    setDeviceMarkers() {
      this.mappingService.markingInfos
        .filter(
          (markingInfo) =>
            markingInfo.markerType === MARKER_TYPE.METER ||
            markingInfo.markerType === MARKER_TYPE.SHS ||
            markingInfo.markerType === MARKER_TYPE.E_BIKE,
        )
        .map((markingInfo) => {
          const deviceMarkerIcon = L.icon({
            ...ICON_OPTIONS,
            iconUrl: ICONS[markingInfo.markerType],
          })
          const deviceMarker = L.marker([markingInfo.lat, markingInfo.lon], {
            icon: deviceMarkerIcon,
          })
          deviceMarker.bindTooltip(markingInfo.serialNumber)

          if (markingInfo.markerType === MARKER_TYPE.METER) {
            const parent = this
            deviceMarker.on("click", () => {
              parent.routeToDetail("/meters", markingInfo.serialNumber)
            })
          }
          if (markingInfo.markerType === MARKER_TYPE.E_BIKE) {
            const parent = this
            deviceMarker.on("click", () => {
              parent.routeToDetailWithQueryParam(
                "/e-bikes",
                "serialNumber",
                markingInfo.serialNumber,
              )
            })
          }
          deviceMarker.addTo(this.editableLayer)
        })
    },
  },
}
</script>

<style scoped>
#map {
  height: 100%;
  min-height: 500px;
  width: 100%;
}

.leaflet-draw-actions a {
  background: white !important;
}
</style>
