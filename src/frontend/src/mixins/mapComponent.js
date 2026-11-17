import { pointInPolygon } from "@/Helpers/Utils.js"
import { MapEngine } from "@/services/map/MapEngine.js"
import { MappingService } from "@/services/MappingService.js"

// Shared behavior for every MPM map component. Owns a single MapEngine instance
// and provides the cluster-polygon, marker and camera helpers the components and
// their parent views rely on.
// Components layer their feature-specific draw/marker handlers on top via
// `this.engine`.
export const mapComponent = {
  props: {
    mappingService: {
      type: MappingService,
      required: true,
    },
    maxZoom: {
      type: Number,
      default: 20,
    },
    polygon: {
      type: Boolean,
      default: false,
    },
    marker: {
      type: Boolean,
      default: false,
    },
    edit: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      engine: null,
      geoDataItems: [],
      clusterPolygons: [],
    }
  },
  mounted() {
    this.initMap()
  },
  beforeDestroy() {
    this.engine?.destroy()
    this.engine = null
  },
  methods: {
    initMap() {
      this.engine = new MapEngine(this.$el, {
        maxZoom: this.maxZoom,
        labels: { resetView: this.$tc("phrases.resetView") },
        countryArea: () =>
          this.mappingService.getCountryArea(
            this.$store.getters["settings/getMainSettings"]?.country,
          ),
      })
      // Drawing/placement maps start empty and must be usable right away, so
      // they go straight to their start view. Every other map waits for its
      // content (and only gets the start view if none arrives).
      if (this.polygon || this.marker) {
        this.engine.showStartView()
      }
    },
    resize() {
      this.engine.resize()
    },
    focusOnItem(latLon) {
      this.engine.focus(latLon)
    },
    routeToDetail(path, id) {
      this.$router.push(`${path}/${id}`)
    },
    routeToDetailWithQueryParam(path, key, value) {
      this.$router.push(`${path}?${key}=${value}`)
    },
    removeExistingMarkers() {
      this.engine.clearMarkers()
    },
    onMapClick(handler) {
      this.engine.whenReady(() =>
        this.engine.map.on("click", (event) =>
          handler({ lat: event.lngLat.lat, lng: event.lngLat.lng }),
        ),
      )
    },
    // Normalize the mapping service's geoData (GeoJSON Features and/or the app's
    // "manual" draw items) into plain GeoJSON Features carrying a clusterId.
    normalizeClusterFeatures() {
      const geoData = this.mappingService.geoData
      const features = Array.isArray(geoData) ? geoData : [geoData]
      return features.map((feature) => {
        if (feature.type === "manual" && feature.geojson) {
          return {
            type: "Feature",
            geometry: feature.geojson,
            properties: {
              clusterId: -1,
              name: feature.display_name || "",
              draw_type: feature.draw_type || "draw",
            },
          }
        }
        if (feature.type !== "Feature") {
          throw new Error("Expected GeoJSON Feature, got: " + feature.type)
        }
        return {
          ...feature,
          properties: {
            ...feature.properties,
            clusterId: feature.properties?.clusterId || -1,
          },
        }
      })
    },
    // Render the cluster polygon(s) held by the mapping service. When `editable`
    // the polygon is loaded into the draw tool for vertex editing; otherwise it
    // is shown as a colored, clickable fill with a cluster-name tooltip.
    drawClusterFeatures({ editable = false, single = false } = {}) {
      this.geoDataItems.splice(0)
      let features = this.normalizeClusterFeatures()
      if (single) features = features.slice(0, 1)
      this.clusterPolygons = features.map((feature) => feature.geometry)

      if (editable) {
        this.engine.clearDraw()
        features.forEach((feature) =>
          this.engine.addDrawFeature(feature.geometry),
        )
        return
      }
      features.forEach((feature) =>
        this.geoDataItems.push({
          type: "manual",
          geojson: feature.geometry,
          display_name: feature.properties?.name || "",
          clusterId: feature.properties?.clusterId ?? -1,
        }),
      )
      this.engine.renderPolygons(features, {
        color: (feature) =>
          this.mappingService.strToHex(feature.properties?.name || ""),
        label: (feature) => feature.properties?.name || "",
        onClick: (feature) => {
          const clusterId = feature.properties?.clusterId ?? -1
          if (clusterId !== -1) {
            this.$router.push({ path: "/clusters/" + clusterId })
          }
        },
      })
    },
    isWithinCluster(latLon) {
      return this.clusterPolygons.some((geometry) =>
        pointInPolygon(latLon, geometry),
      )
    },
    // Place a single marker, replacing any markers already on the map.
    placeSingleMarker(latLon, iconUrl) {
      this.removeExistingMarkers()
      return this.engine.addMarker({ lat: latLon[0], lon: latLon[1], iconUrl })
    },
    // Device markers added with `editable: true` can then only be moved through
    // the map's "Edit Locations" mode; saving emits `locationEdited` once with
    // every moved device.
    enableLocationEditing() {
      this.engine.enableLocationEditing({
        labels: {
          edit: this.$tc("phrases.editLocations"),
          save: this.$tc("words.save"),
          cancel: this.$tc("words.cancel"),
        },
        onSave: (changes) => this.saveEditedLocations(changes),
      })
    },
    saveEditedLocations(changes) {
      this.emitLocationEdits(changes)
    },
    emitLocationEdits(changes) {
      if (!changes.length) return
      this.$emit(
        "locationEdited",
        changes.map(({ data, lat, lon }) => ({
          serialNumber: data.serialNumber,
          lat,
          lon,
        })),
      )
    },
  },
}
