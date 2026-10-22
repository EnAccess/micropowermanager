import L from "leaflet"
import marker from "leaflet/dist/images/marker-icon.png"
import "leaflet/dist/leaflet.css"
import "leaflet-bing-layer"
import "leaflet-draw"
import "leaflet-draw/dist/leaflet.draw.css"
import "leaflet.featuregroup.subgroup"
import "leaflet.markercluster"
import "leaflet.markercluster/dist/MarkerCluster.css"
import "leaflet.markercluster/dist/MarkerCluster.Default.css"

import { ICON_OPTIONS, MappingService } from "@/services/MappingService.js"
import { EventBus } from "@/shared/eventbus.js"
import store from "@/store/store.js"

export const sharedMap = {
  props: {
    mappingService: {
      type: MappingService,
      required: true,
    },
    zoom: {
      type: Number,
      default: 7,
    },
    maxZoom: {
      type: Number,
      default: 20,
    },

    mutatingCenter: {
      type: Array,
      required: false,
    },
    position: {
      type: String,
      default: "topright",
      required: false,
    },
    polygon: {
      type: Boolean,
      default: false,
    },
    polyline: {
      type: Boolean,
      default: false,
    },
    marker: {
      type: Boolean,
      default: false,
    },
    markerCount: {
      type: Number,
      default: 1,
    },
    circlemarker: {
      type: Boolean,
      default: false,
    },
    rectangle: {
      type: Boolean,
      default: false,
    },
    circle: {
      type: Boolean,
      default: false,
    },
    remove: {
      type: Boolean,
      default: false,
    },
    edit: {
      type: Boolean,
      default: false,
    },
    mapContainerId: {
      type: String,
      default: "map",
    },
  },
  data() {
    return {
      markerUrl: this.mappingService ? this.mappingService.markerUrl : null,
      defaultMarkerIconUrl: marker,
      osmUrl: "http://{s}.tile.osm.org/{z}/{x}/{y}.png",
      osmAttrib: '<span style="cursor:pointer">&copy; MpManager</span>',
      osm: null,
      map: null,
      editableLayer: null,
      nonEditableLayer: null,
      markersLayer: null,
      drawingOptions: {},
      geoDataItems: [],
    }
  },
  mounted() {
    this.initMap()
  },
  destroyed() {
    if (this.map) {
      this.map.remove()
      this.map = null
    }
  },
  methods: {
    initMap() {
      const defaultZoom = store.getters["settings/getMapSettings"]?.zoom || 7
      const validZoom =
        this.zoom != null && !isNaN(this.zoom) && this.zoom > 0
          ? this.zoom
          : defaultZoom

      const defaultLat =
        store.getters["settings/getMapSettings"]?.latitude ?? -2.50038
      const defaultLon =
        store.getters["settings/getMapSettings"]?.longitude ?? 32.88906
      const validCenter =
        this.mappingService &&
        this.mappingService.center &&
        this.mappingService.center[0] != null &&
        this.mappingService.center[1] != null &&
        !isNaN(this.mappingService.center[0]) &&
        !isNaN(this.mappingService.center[1])
          ? this.mappingService.center
          : [defaultLat, defaultLon]

      const drawingOptions = {
        position: this.position,
        draw: {
          polygon: this.polygon,
          polyline: this.polyline,
          marker: this.marker,
          circlemarker: this.circlemarker,
          rectangle: this.rectangle,
          circle: this.circle,
        },
        edit: {
          featureGroup: new L.FeatureGroup(),
          remove: this.remove,
          edit: this.edit,
        },
      }
      this.map = L.map(this.mapContainerId).setView(
        validCenter,
        validZoom,
      )
      this.setTileLayer()
      if (drawingOptions.draw.marker) {
        const marker = L.Icon.extend({
          options: { ...ICON_OPTIONS, iconUrl: this.markerUrl },
        })
        drawingOptions.draw.marker = {}
        drawingOptions.draw.marker.icon = new marker()
      }
      this.markersLayer = new L.markerClusterGroup({
        chunkedLoading: true,
        spiderfyOnMaxZoom: true,
      })
      const drawControl = new L.Control.Draw(drawingOptions)
      this.map.addLayer(drawingOptions.edit.featureGroup)
      this.map.addControl(drawControl)
      this.editableLayer = drawingOptions.edit.featureGroup
      this.nonEditableLayer = new L.FeatureGroup()
    },
    setTileLayer() {
      if (!this.map) return
      L.tileLayer(this.osmUrl, {
        maxZoom: this.maxZoom,
        attribution: this.osmAttrib,
      }).addTo(this.map)
    },
    reGenerateMap(mutatingCenter) {
      if (!this.map) return
      const currentZoom =
        this.zoom != null && !isNaN(this.zoom) && this.zoom > 0
          ? this.zoom
          : store.getters["settings/getMapSettings"]?.zoom || 7
      if (
        mutatingCenter &&
        mutatingCenter[0] != null &&
        mutatingCenter[1] != null &&
        !isNaN(mutatingCenter[0]) &&
        !isNaN(mutatingCenter[1])
      ) {
        this.map.flyTo(mutatingCenter, currentZoom, this.drawingOptions)
      }
    },
    routeToDetail(path, id) {
      this.$router.push(`${path}/${id}`)
    },
    routeToDetailWithQueryParam(path, key, value) {
      this.$router.push(`${path}?${key}=${value}`)
    },
    focusOnItem(newLat, newLng) {
      if (!this.map) return
      const currentZoom =
        this.zoom != null && !isNaN(this.zoom) && this.zoom > 0
          ? this.zoom
          : store.getters["settings/getMapSettings"]?.zoom || 7
      if (
        newLat != null &&
        newLng != null &&
        !isNaN(newLat) &&
        !isNaN(newLng)
      ) {
        this.map.setView([newLat, newLng], currentZoom)
      }
    },
    getLatLng() {
      if (!this.map) {
        return { lat: "0.00000", lng: "0.00000", zoom: 7 }
      }
      let zoom
      this.map.on("move", function (e) {
        zoom = Math.round(e.target._zoom)
        EventBus.$emit("mapZoom", zoom)
      })
      const center = this.map.getCenter()
      return {
        lat: center ? center.lat.toFixed(5) : "0.00000",
        lng: center ? center.lng.toFixed(5) : "0.00000",
        zoom: zoom || 7,
      }
    },
    removeExistingMarkers() {
      if (!this.map) return
      this.map.eachLayer(function (layer) {
        if (layer._icon) {
          layer.remove()
        }
      })
    },
  },
  computed: {
    mapProvider() {
      return store.getters["settings/getMapSettings"].provider === "Bing Maps"
    },
  },
  watch: {
    mutatingCenter() {
      this.reGenerateMap(this.mutatingCenter)
    },
  },
}
