<template>
  <div id="map"></div>
</template>

<script>
import { sharedMap, notify } from "@/mixins"
import { ICON_OPTIONS, ICONS, MARKER_TYPE } from "@/services/MappingService"
import { MiniGridService } from "@/services/MiniGridService"
import { ClusterService } from "@/services/ClusterService"
import { MiniGridDeviceService } from "@/services/MiniGridDeviceService"

export default {
  name: "MiniGridMap",
  mixins: [sharedMap, notify],
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
    const drawingLayer = this.editableLayer
    const markersLayer = this.markersLayer
    const map = this.map
    this.map.on("draw:created", (event) => {
      const type = event.layerType
      const layer = event.layer
      // const drawnLayers = drawingLayer.getLayers()
      let cluster = null
      map.eachLayer(function (layer) {
        if (layer.getBounds) {
          cluster = layer
        }
      })
      const bounds = cluster.getBounds()
      this.removeExistingMarkers()
      if (bounds.contains(layer._latlng)) {
        drawingLayer.addLayer(layer)
        const geoDataItem = {
          type: type,
          coordinates: layer._latlng,
        }

        this.$emit("locationSet", {
          error: undefined,
          geoDataItem: geoDataItem,
        })
      } else {
        const errorMessage =
          "Please position your mini-grid within the selected cluster boundaries."
        this.$emit("locationSet", {
          error: errorMessage,
          geoDataItem: undefined,
        })
      }
    })
    this.map.on("draw:edited", (event) => {
      let cluster = null
      map.eachLayer(function (layer) {
        if (layer.getBounds) {
          cluster = layer
        }
      })
      const bounds = cluster.getBounds()
      const editedItems = []
      const editedLayers = event.layers
      editedLayers.eachLayer((layer) => {
        if (bounds.contains(layer._latlng)) {
          const geoDataItem = {
            serialNumber: layer._tooltip._content,
            lat: layer._latlng.lat,
            lon: layer._latlng.lng,
          }
          editedItems.push(geoDataItem)
        } else {
          const errorMessage =
            "Please position your device within the selected cluster boundaries. Otherwise, it will not be updated."
          this.alertNotify("warning", errorMessage)
        }
      })

      if (editedItems.length) {
        this.$emit("locationEdited", editedItems)
      }
    })
    this.map.on("draw:toolbaropened", function () {
      map.removeLayer(markersLayer)
      const markers = markersLayer.getLayers()
      drawingLayer.eachLayer((layer) => {
        const type = !layer._latlngs ? "Marker" : "Polygon"
        if (type === "Marker") {
          drawingLayer.removeLayer(layer)
        }
      })
      markers.map((marker) => {
        marker.setOpacity(1)
        marker.addTo(drawingLayer)
      })
      map.addLayer(drawingLayer)
    })
  },
  methods: {
    drawCluster() {
      this.editableLayer.clearLayers()
      const geoData = this.mappingService.geoData

      const features = Array.isArray(geoData) ? geoData : [geoData]

      const feature = features[0]

      if (feature.type !== "Feature") {
        throw new Error("Expected GeoJSON Feature, got: " + feature.type)
      }

      const featureCollection = {
        type: "FeatureCollection",
        features: [feature],
      }

      const polygonColor = this.mappingService.strToHex(
        feature.properties?.clusterDisplayName ||
          feature.properties?.display_name ||
          feature.properties?.name ||
          "",
      )

      const nonEditableLayers = this.nonEditableLayer
      const geoDataItems = this.geoDataItems
      const map = this.map
      const parent = this

      const drawnCluster = L.geoJSON(featureCollection, {
        style: { fillColor: polygonColor, color: polygonColor },
        onEachFeature: function (feature, layer) {
          const clusterId = feature.properties?.clusterId || -1
          const displayName =
            feature.properties?.clusterDisplayName ||
            feature.properties?.display_name ||
            feature.properties?.name ||
            ""

          if (feature.geometry.type === "Polygon" && clusterId !== -1) {
            layer.on("click", () => {
              parent.$router.push({
                path: "/clusters/" + clusterId,
              })
            })
          }

          nonEditableLayers.addLayer(layer)
          map.addLayer(nonEditableLayers)
          layer.bindTooltip("<strong>Cluster:</strong> " + displayName, {
            sticky: true,
            offset: [10, 10],
          })

          const geoDataItem = {
            leaflet_id: layer._leaflet_id,
            type: "manual",
            geojson: feature.geometry,
            display_name: displayName,
            clusterId: clusterId,
          }
          geoDataItems.push(geoDataItem)
        },
      })

      const bounds = drawnCluster.getBounds()
      if (bounds.isValid()) {
        this.map.fitBounds(bounds)
      }
    },
    setMiniGridMarkers() {
      this.mappingService.markingInfos
        .filter(
          (markingInfo) => markingInfo.markerType === MARKER_TYPE.MINI_GRID,
        )
        .map((markingInfo) => {
          const miniGridMarkerIcon = L.icon({
            ...ICON_OPTIONS,
            iconUrl: ICONS[markingInfo.markerType],
          })
          const miniGridMarker = L.marker([markingInfo.lat, markingInfo.lon], {
            icon: miniGridMarkerIcon,
          })
          let tooltip = "<strong>Mini Grid:</strong> " + markingInfo.name
          if (markingInfo.clusterId !== undefined) {
            tooltip +=
              "<br><strong>Cluster:</strong> " + markingInfo.clusterDisplayName
          }
          miniGridMarker.bindTooltip(tooltip, {
            sticky: true,
            offset: [10, 10],
          })

          miniGridMarker.addTo(this.map)
        })
    },
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
          deviceMarker.bindTooltip(
            "<strong>Device:</strong> " +
              markingInfo.serialNumber +
              "<br>" +
              "<strong>Type:</strong> " +
              markingInfo.deviceType,
            { sticky: true, offset: [10, 10] },
          )

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
          deviceMarker.addTo(this.markersLayer) //this layer is used to show markers as marker cluster

          const editableMarker = L.marker([markingInfo.lat, markingInfo.lon], {
            icon: deviceMarkerIcon,
          }).setOpacity(0)
          editableMarker.addTo(this.editableLayer) //we create invisible editable markers as well to be able to edit them once toolbar opened
        })
      this.map.addLayer(this.markersLayer)
    },
    setMiniGridMarkerManually(location) {
      const editableLayers = this.nonEditableLayer.getLayers()
      const polygon = editableLayers.find(
        (layer) => layer.feature && layer.feature.geometry.type === "Polygon",
      )
      const bounds = polygon.getBounds()
      if (!bounds.contains(location)) {
        const errorMessage =
          "Please position your mini-grid within the selected cluster boundaries."
        this.$emit("locationSet", {
          error: errorMessage,
          geoDataItem: undefined,
        })
      } else {
        this.removeExistingMarkers()

        const miniGridMarkerIcon = L.icon({
          ...ICON_OPTIONS,
          iconUrl: ICONS.MINI_GRID,
        })

        const miniGridMarker = L.marker(location, {
          icon: miniGridMarkerIcon,
        })
        miniGridMarker.addTo(this.editableLayer)
      }
    },
    isMiniGridIdProvided() {
      if (this.miniGridId !== undefined && this.miniGridId !== "") {
        return true
      }
      return false
    },
    async setMiniGridMapData(miniGridId) {
      const markingInfos = []
      const miniGridWithGeoData =
        await this.miniGridService.getMiniGridGeoData(miniGridId)
      const points = miniGridWithGeoData.location.points.split(",")
      if (points.length !== 2) {
        this.alertNotify("error", "Mini-Grid has no location")
        return
      }
      const lat = parseFloat(points[0])
      const lon = parseFloat(points[1])
      const clusterId = miniGridWithGeoData.cluster_id
      const clusterGeoJson =
        await this.clusterService.getClusterGeoLocation(clusterId)

      // Convert geo_json to GeoJSON Feature
      if (!clusterGeoJson.geo_json) {
        throw new Error("clusterGeoJson.geo_json is required")
      }

      let geoJsonFeature
      if (clusterGeoJson.geo_json.type === "Feature") {
        geoJsonFeature = clusterGeoJson.geo_json
      } else if (clusterGeoJson.geo_json.type === "FeatureCollection") {
        geoJsonFeature = clusterGeoJson.geo_json.features[0]
      } else {
        throw new Error(
          "clusterGeoJson.geo_json must be a GeoJSON Feature or FeatureCollection",
        )
      }

      this.mappingService.setGeoData(geoJsonFeature)
      markingInfos.push({
        id: miniGridWithGeoData.id,
        name: miniGridWithGeoData.name,
        serialNumber: null,
        lat: lat,
        lon: lon,
        deviceType: null,
        markerType: MARKER_TYPE.MINI_GRID,
      })
      const devicesInMiniGrid =
        await this.miniGridDeviceService.getMiniGridDevices(miniGridId)
      devicesInMiniGrid.map((device) => {
        const points = device.address.geo.points.split(",")
        if (points.length !== 2) {
          return
        }
        const lat = parseFloat(points[0])
        const lon = parseFloat(points[1])

        let markerType = ""
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
          name: miniGridWithGeoData.name,
          serialNumber: device.device_serial,
          addressId: device.address.id,
          lat: lat,
          lon: lon,
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
