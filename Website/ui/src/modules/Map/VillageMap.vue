<template>
  <div id="map"></div>
</template>

<script>
import { sharedMap, notify } from "@/mixins"
import { ICON_OPTIONS, ICONS, MARKER_TYPE } from "@/services/MappingService"

export default {
  name: "VillageMap",
  mixins: [sharedMap, notify],
  mounted() {
    const drawingLayer = this.editableLayer
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
          "Please position your village within the selected cluster boundaries."
        this.$emit("locationSet", {
          error: errorMessage,
          geoDataItem: undefined,
        })
      }
    })
  },
  methods: {
    drawCluster() {
      this.editableLayer.clearLayers()
      const geoData = this.mappingService.geoData.geo_data
      const geoType = geoData.geojson.type
      const coordinatesClone = geoData.geojson.coordinates[0].reduce(
        (acc, coord) => {
          acc[0].push([coord[1], coord[0]])
          return acc
        },
        [[]],
      )
      const drawing = {
        type: "FeatureCollection",
        crs: {
          type: "name",
          properties: {
            name: "urn:ogc:def:crs:OGC:1.3:CRS84",
          },
        },
        features: [
          {
            type: "Feature",
            properties: {
              popupContent: geoData.display_name,
              draw_type:
                geoData.draw_type === undefined ? "set" : geoData.draw_type,
              selected:
                geoData.selected === undefined ? false : geoData.selected,
              clusterId:
                geoData.clusterId === undefined ? -1 : geoData.clusterId,
            },
            geometry: {
              type: geoType,
              coordinates: geoData.searched
                ? geoData.geojson.coordinates
                : coordinatesClone,
            },
          },
        ],
      }
      const polygonColor = this.mappingService.strToHex(geoData.display_name)
      // "this"  cannot be used inside the L.geoJson function
      const editableLayer = this.editableLayer
      const geoDataItems = this.geoDataItems
      const drawnCluster = L.geoJson(drawing, {
        style: { fillColor: polygonColor, color: polygonColor },
        onEachFeature: function (feature, layer) {
          const type = layer.feature.geometry.type
          const clusterId = layer.feature.properties.clusterId
          if (type === "Polygon" && clusterId !== -1) {
            layer.on("click", () => {
              this.$router.push({
                path: "/clusters/" + clusterId,
              })
            })
          }
          editableLayer.addLayer(layer)

          const geoDataItem = {
            leaflet_id: layer._leaflet_id,
            type: "manual",
            geojson: {
              type: geoData.geojson.type,
              coordinates:
                geoData.searched === true
                  ? coordinatesClone
                  : geoData.geojson.coordinates,
            },
            searched: false,
            display_name: geoData.display_name,
            selected: feature.properties.selected,
            draw_type: feature.properties.draw_type,
            lat: geoData.lat,
            lon: geoData.lon,
          }
          geoDataItems.push(geoDataItem)
        },
      })
      const bounds = drawnCluster.getBounds()
      this.map.fitBounds(bounds)
    },
    setMiniGridMarker() {
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
          miniGridMarker.bindTooltip("Mini Grid: " + markingInfo.name)
          const parent = this
          miniGridMarker.on("click", () => {
            parent.routeToDetail(markingInfo.id, markingInfo.name)
          })
          miniGridMarker.addTo(this.map)
        })
    },
    setVillageMarkerManually(location) {
      const editableLayers = this.editableLayer.getLayers()
      const polygon = editableLayers.find(
        (layer) => layer.feature && layer.feature.geometry.type === "Polygon",
      )
      const bounds = polygon.getBounds()

      if (!bounds.contains(location)) {
        const errorMessage =
          "Please position your village within the selected cluster boundaries."
        this.$emit("locationSet", {
          error: errorMessage,
          geoDataItem: undefined,
        })
      } else {
        this.removeExistingMarkers()

        const villageMarkerIcon = L.icon({
          ...ICON_OPTIONS,
          iconUrl: ICONS.VILLAGE,
        })

        const villageMarker = L.marker(location, {
          icon: villageMarkerIcon,
        })
        villageMarker.addTo(this.editableLayer)
      }
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
