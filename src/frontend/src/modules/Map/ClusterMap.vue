<template>
  <div id="map"></div>
</template>

<script>
import { sharedMap, notify } from "@/mixins"
import { EventBus } from "@/shared/eventbus"
import { ICON_OPTIONS, ICONS, MARKER_TYPE } from "@/services/MappingService"

export default {
  name: "ClusterMap",
  mixins: [sharedMap, notify],
  mounted() {
    const drawingLayer = this.editableLayer
    const service = this.mappingService
    this.map.on("draw:created", (item) => {
      const layer = item.layer
      const feature = (layer.feature = layer.feature || {})
      feature.type = feature.type || "Feature"
      const props = (feature.properties = feature.properties || {})
      props.draw_type = "draw"
      props.selected = false
      drawingLayer.addLayer(layer)

      const { sumLat, sumLon } = layer._latlngs[0].reduce(
        (acc, coordinates) => {
          acc.sumLat += coordinates.lat
          acc.sumLon += coordinates.lng
          return acc
        },
        { sumLat: 0, sumLon: 0 },
      )

      const avgLat = sumLat / layer._latlngs[0].length
      const avgLon = sumLon / layer._latlngs[0].length
      const geoDataItem = service.manualDrawingLocationConvert({
        leaflet_id: layer._leaflet_id,
        type: "manual",
        geojson: {
          type: "Polygon",
          coordinates: layer._latlngs,
        },
        display_name: "",
        selected: false,
        draw_type: "draw",
        lat: avgLat,
        lon: avgLon,
      })
      // this.$emit throws error interestingly
      EventBus.$emit("customDrawnSet", geoDataItem)
    })
    this.map.on("draw:deleted", (item) => {
      const deletedItems = []
      const deletedLayers = item.layers
      deletedLayers.eachLayer((layer) => {
        deletedItems.push(layer)
      })
      this.$emit("customDrawnDeleted", deletedItems)
    })
    this.map.on("draw:edited", (item) => {
      const editedItems = []
      const editedLayers = item.layers
      editedLayers.eachLayer((layer) => {
        const { sumLat, sumLon } = layer._latlngs[0].reduce(
          (acc, coordinates) => {
            acc.sumLat += coordinates.lat
            acc.sumLon += coordinates.lng
            return acc
          },
          { sumLat: 0, sumLon: 0 },
        )

        const avgLat = sumLat / layer._latlngs[0].length
        const avgLon = sumLon / layer._latlngs[0].length
        const geoDataItem = service.manualDrawingLocationConvert({
          leaflet_id: layer._leaflet_id,
          type: "manual",
          geojson: {
            type: "Polygon",
            coordinates: layer._latlngs,
          },
          display_name: "",
          selected: false,
          lat: avgLat,
          lon: avgLon,
        })
        editedItems.push(geoDataItem)
      })
      this.$emit("customDrawnEdited", editedItems)
    })
  },
  methods: {
    drawCluster() {
      this.editableLayer.clearLayers()
      const geoData = this.mappingService.geoData

      // Handle both single feature and array of features
      const features = Array.isArray(geoData) ? geoData : [geoData]

      // Create FeatureCollection with cluster properties
      const featureCollection = {
        type: "FeatureCollection",
        features: features.map((feature) => {
          if (feature.type !== "Feature") {
            throw new Error("Expected GeoJSON Feature, got: " + feature.type)
          }
          return {
            ...feature,
            properties: {
              ...feature.properties,
              clusterId: feature.properties?.clusterId || -1,
              clusterDisplayName:
                feature.properties?.clusterDisplayName ||
                feature.properties?.display_name ||
                feature.properties?.name ||
                "",
            },
          }
        }),
      }

      const editableLayer = this.editableLayer
      const geoDataItems = this.geoDataItems
      const parent = this

      const drawnCluster = L.geoJSON(featureCollection, {
        style: (feature) => {
          const displayName =
            feature.properties?.clusterDisplayName ||
            feature.properties?.display_name ||
            feature.properties?.name ||
            ""
          const polygonColor = this.mappingService.strToHex(displayName)
          return { fillColor: polygonColor, color: polygonColor }
        },
        onEachFeature: function (feature, layer) {
          const clusterId = feature.properties?.clusterId || -1
          const displayName =
            feature.properties?.clusterDisplayName ||
            feature.properties?.display_name ||
            feature.properties?.name ||
            ""

          if (
            feature.geometry.type.toLowerCase() === "polygon" &&
            clusterId !== -1
          ) {
            layer.on("click", () => {
              parent.$router.push({
                path: "/clusters/" + clusterId,
              })
            })
          }

          editableLayer.addLayer(layer)
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
      const control = L.control.layers(null, null, { collapsed: false })
      control.addTo(this.map)
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

          const parent = this
          miniGridMarker.on("click", function () {
            parent.routeToDetail("/dashboards/mini-grid", markingInfo.id)
          })

          miniGridMarker.addTo(this.markersLayer)
        })
      this.map.addLayer(this.markersLayer)
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
