<template>
  <div class="mpm-map"></div>
</template>

<script>
import { geoJsonToLatLon } from "@/Helpers/Utils.js"
import { mapComponent } from "@/mixins/mapComponent.js"
import { notify } from "@/mixins/notify.js"
import { ClusterService } from "@/services/ClusterService.js"
import { ICONS, MARKER_TYPE, markerTooltip } from "@/services/MappingService.js"
import { MiniGridDeviceService } from "@/services/MiniGridDeviceService.js"
import { MiniGridService } from "@/services/MiniGridService.js"

const DEVICE_MARKER_TYPES = [
  MARKER_TYPE.METER,
  MARKER_TYPE.SHS,
  MARKER_TYPE.E_BIKE,
]
const DEVICE_LAYER_LABELS = {
  [MARKER_TYPE.METER]: "Meters",
  [MARKER_TYPE.SHS]: "Solar Home Systems",
  [MARKER_TYPE.E_BIKE]: "E-Bikes",
}

export default {
  name: "MiniGridMap",
  mixins: [mapComponent, notify],
  props: {
    miniGridId: {
      // eslint-disable-next-line vue/require-prop-type-constructor
      type: Number | String,
      required: false,
    },
  },
  data() {
    return {
      clusterService: new ClusterService(),
      miniGridService: new MiniGridService(),
      miniGridDeviceService: new MiniGridDeviceService(),
    }
  },
  mounted() {
    if (this.isMiniGridIdProvided()) {
      this.setMiniGridMapData(this.miniGridId)
    }
    if (this.marker) {
      this.onMapClick(this.placeMiniGridFromClick)
    }
    if (this.edit) {
      this.enableLocationEditing()
    }
  },
  methods: {
    saveEditedLocations(changes) {
      const outsideCluster = changes.filter(
        ({ lat, lon }) => !this.isWithinCluster([lat, lon]),
      )
      if (outsideCluster.length) {
        outsideCluster.forEach((change) => change.revert())
        this.alertNotify(
          "warning",
          "Devices must stay within the cluster boundaries. Devices moved outside it were put back.",
        )
      }
      this.emitLocationEdits(
        changes.filter((change) => !outsideCluster.includes(change)),
      )
    },
    placeMiniGridFromClick({ lat, lng }) {
      const location = [lat, lng]
      this.removeExistingMarkers()
      if (!this.isWithinCluster(location)) {
        this.$emit("locationSet", {
          error:
            "Please position your site within the selected cluster boundaries.",
          geoDataItem: undefined,
        })
        return
      }
      this.placeSingleMarker(location, this.markerIconUrl())
      this.$emit("locationSet", {
        error: undefined,
        geoDataItem: { type: "marker", coordinates: { lat, lng } },
      })
    },
    markerIconUrl() {
      return this.mappingService.markerUrl || ICONS.MINI_GRID
    },
    drawCluster() {
      this.drawClusterFeatures({ single: true })
    },
    setMiniGridMarkers() {
      this.mappingService.markingInfos
        .filter(
          (markingInfo) => markingInfo.markerType === MARKER_TYPE.MINI_GRID,
        )
        .forEach((markingInfo) => {
          this.engine.addMarker({
            lat: markingInfo.lat,
            lon: markingInfo.lon,
            iconUrl: ICONS[markingInfo.markerType],
            tooltipHtml: markerTooltip(markingInfo),
          })
        })
    },
    setDeviceMarkers() {
      const presentTypes = new Set()
      this.mappingService.markingInfos
        .filter((markingInfo) =>
          DEVICE_MARKER_TYPES.includes(markingInfo.markerType),
        )
        .forEach((markingInfo) => {
          presentTypes.add(markingInfo.markerType)
          this.engine.addMarker({
            group: markingInfo.markerType,
            lat: markingInfo.lat,
            lon: markingInfo.lon,
            iconUrl: ICONS[markingInfo.markerType],
            tooltipHtml: markerTooltip(markingInfo),
            editable: this.edit,
            data: { serialNumber: markingInfo.serialNumber },
            onClick: this.deviceClickHandler(markingInfo),
          })
        })

      const labels = {}
      presentTypes.forEach((type) => {
        labels[type] = DEVICE_LAYER_LABELS[type]
      })
      if (Object.keys(labels).length > 0) {
        this.engine.addMarkerGroupToggle(labels)
      }
    },
    deviceClickHandler(markingInfo) {
      if (markingInfo.markerType === MARKER_TYPE.METER) {
        return () => this.routeToDetail("/meters", markingInfo.serialNumber)
      }
      if (markingInfo.markerType === MARKER_TYPE.E_BIKE) {
        return () =>
          this.routeToDetailWithQueryParam(
            "/e-bikes",
            "serialNumber",
            markingInfo.serialNumber,
          )
      }
      return undefined
    },
    setMiniGridMarkerManually(location) {
      if (!this.isWithinCluster(location)) {
        this.$emit("locationSet", {
          error: this.$tc("phrases.positionMiniGridInCluster"),
          geoDataItem: undefined,
        })
        return
      }
      this.placeSingleMarker(location, ICONS.MINI_GRID)
    },
    isMiniGridIdProvided() {
      return this.miniGridId !== undefined && this.miniGridId !== ""
    },
    async setMiniGridMapData(miniGridId) {
      const markingInfos = []
      const miniGridWithGeoData =
        await this.miniGridService.getMiniGridGeoData(miniGridId)
      const location = geoJsonToLatLon(miniGridWithGeoData.location)
      if (location == null) {
        this.alertNotify("error", "Site has no location")
        return
      }
      const clusterId = miniGridWithGeoData.cluster_id
      const clusterGeoData =
        await this.clusterService.getClusterGeoLocation(clusterId)

      const clusterFeature =
        this.mappingService.setClusterGeoData(clusterGeoData)
      if (!clusterFeature) {
        throw new Error("clusterGeoData.geo_json is required")
      }
      const clusterName = clusterFeature.properties?.name || ""
      const siteName = miniGridWithGeoData.name

      markingInfos.push({
        id: miniGridWithGeoData.id,
        name: siteName,
        clusterName: clusterName,
        siteName: siteName,
        serialNumber: null,
        lat: location.lat,
        lon: location.lon,
        deviceType: null,
        markerType: MARKER_TYPE.MINI_GRID,
      })
      const devicesInMiniGrid =
        await this.miniGridDeviceService.getMiniGridDevices(miniGridId)
      devicesInMiniGrid.forEach((device) => {
        const deviceLocation = geoJsonToLatLon(device.geo)
        if (deviceLocation == null) {
          return
        }

        let markerType
        switch (device.device_type) {
          case "e_bike":
            markerType = MARKER_TYPE.E_BIKE
            break
          case "solar_home_system":
            markerType = MARKER_TYPE.SHS
            break
          default:
            markerType = MARKER_TYPE.METER
        }
        markingInfos.push({
          id: miniGridWithGeoData.id,
          name: siteName,
          clusterName: clusterName,
          siteName: siteName,
          serialNumber: device.device_serial,
          lat: deviceLocation.lat,
          lon: deviceLocation.lon,
          deviceType: device.device_type,
          markerType: markerType,
        })
      })
      this.mappingService.setMarkingInfos(markingInfos)
      this.drawCluster()
      this.setMiniGridMarkers()
      this.setDeviceMarkers()
    },
  },
}
</script>

<style scoped lang="scss">
.mpm-map {
  height: 100%;
  min-height: 500px;
  width: 100%;
}
</style>
