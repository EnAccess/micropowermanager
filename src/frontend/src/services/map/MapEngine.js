import MapboxDraw from "@mapbox/mapbox-gl-draw"
import "@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css"
import * as maplibregl from "maplibre-gl"
import "maplibre-gl/dist/maplibre-gl.css"

import {
  LocateResetControl,
  LocationEditControl,
  MarkerGroupToggleControl,
} from "./controls.js"
import "./draw-cursors.css"

// maplibre-gl starts its tile worker from a separate file whose name it builds
// at runtime, so webpack never emits it. Referencing it via `new URL(...,
// import.meta.url)` makes webpack emit the file, and we point maplibre at it.
maplibregl.setWorkerUrl(
  new URL("maplibre-gl/dist/maplibre-gl-worker.mjs", import.meta.url).href,
)

// mapbox-gl-draw ships with Mapbox CSS class names; re-point them at MapLibre's
// classes so its toolbar and canvas line up with MapLibre (as in MapLibre's
// "draw polygon with mapbox-gl-draw" example).
MapboxDraw.constants.classes.CANVAS = "maplibregl-canvas"
MapboxDraw.constants.classes.CONTROL_BASE = "maplibregl-ctrl"
MapboxDraw.constants.classes.CONTROL_PREFIX = "maplibregl-ctrl-"
MapboxDraw.constants.classes.CONTROL_GROUP = "maplibregl-ctrl-group"
MapboxDraw.constants.classes.ATTRIBUTION = "maplibregl-ctrl-attrib"

// Keyless OpenFreeMap vector basemap.
const BASEMAP_STYLE = "https://tiles.openfreemap.org/styles/bright"

// Display size for image markers; the source icons are much larger.
const ICON_MARKER_SIZE = 40

// Camera limits: how far to zoom in when fitting bounds, the zoom for a single
// marker, and the zoom that shows the whole world when there is nothing else to
// show.
const MAX_FIT_ZOOM = 15
const SINGLE_MARKER_ZOOM = 14
const WORLD_ZOOM = 1

// Every visible camera move is a flyTo of this fixed length. A linear ease
// across many zoom levels flings the map thousands of pixels per frame, and
// flyTo's own speed-based duration takes several seconds for long trips.
const CAMERA_DURATION_MS = 1000

// A loaded map that still has no content after this long is treated as empty
// and gets its start view; a map that failed to load is simply revealed.
const EMPTY_MAP_TIMEOUT_MS = 3000

const POLYGON_SOURCE = "mpm-polygons"
const POLYGON_FILL_LAYER = "mpm-polygons-fill"
const POLYGON_LINE_LAYER = "mpm-polygons-line"
const POLYGON_LABEL_LAYER = "mpm-polygons-label"

// Every [lng, lat] position of a GeoJSON geometry, so a bounding box can be
// computed without pulling in a geometry library.
const collectPositions = (geometry, positions) => {
  if (!geometry) return
  const { type, coordinates } = geometry
  if (type === "Point") {
    positions.push(coordinates)
  } else if (type === "MultiPoint" || type === "LineString") {
    positions.push(...coordinates)
  } else if (type === "MultiLineString" || type === "Polygon") {
    coordinates.forEach((ring) => positions.push(...ring))
  } else if (type === "MultiPolygon") {
    coordinates.forEach((polygon) =>
      polygon.forEach((ring) => positions.push(...ring)),
    )
  }
}

const iconElement = (iconUrl) => {
  const element = document.createElement("img")
  element.src = iconUrl
  element.style.width = `${ICON_MARKER_SIZE}px`
  element.style.height = `${ICON_MARKER_SIZE}px`
  return element
}

// Framework-agnostic wrapper around MapLibre GL JS and mapbox-gl-draw: one map
// with the markers, polygons and drawing the MPM map components need.
// Coordinates passed in by the app are [lat, lon]; MapLibre's [lng, lat] stays
// inside this class.
export class MapEngine {
  // `countryArea` is an async function returning [[west, south], [east, north]]
  // (or null) for maps that have nothing to show; see showStartView.
  constructor(container, { maxZoom = 20, labels, countryArea } = {}) {
    this.countryArea = countryArea
    this.markers = []
    this.polygonFeatures = []
    this.draw = null
    this.locationEditControl = null
    this.fitScheduled = false

    this.map = new maplibregl.Map({
      container,
      style: BASEMAP_STYLE,
      maxZoom,
    })
    this.map.addControl(
      new maplibregl.NavigationControl({ showCompass: false }),
    )
    this.map.addControl(
      new LocateResetControl({
        label: labels.resetView,
        onReset: () => this.resetView(),
        fitBoundsOptions: {
          maxZoom: MAX_FIT_ZOOM,
          duration: CAMERA_DURATION_MS,
        },
      }),
    )

    this.loaded = false
    this.ready = new Promise((resolve) =>
      this.map.once("load", () => {
        this.loaded = true
        resolve()
      }),
    )

    // Keep the map invisible until it has been positioned, so a page never
    // shows an arbitrary view and then jumps. If no content arrives, the map
    // gets its start view; a map that fails to load is revealed regardless
    // instead of silently disappearing (hence started here, not on "load").
    this.revealed = false
    this.map.getContainer().style.opacity = "0"
    this.emptyMapTimeout = setTimeout(() => {
      if (this.revealed) return
      if (this.loaded && this.isEmpty()) {
        this.showStartView()
      } else {
        this.reveal()
      }
    }, EMPTY_MAP_TIMEOUT_MS)
  }

  whenReady(callback) {
    this.ready.then(() => {
      if (this.map) callback()
    })
  }

  destroy() {
    clearTimeout(this.emptyMapTimeout)
    this.map?.remove()
    this.map = null
    this.markers = []
  }

  resize() {
    this.map?.resize()
  }

  reveal() {
    if (this.revealed) return
    this.revealed = true
    this.map.getContainer().style.opacity = "1"
  }

  focus(latLon) {
    this.whenReady(() =>
      this.showPositions([[Number(latLon[1]), Number(latLon[0])]]),
    )
  }

  contentPositions() {
    const positions = this.markers.map(({ marker }) =>
      marker.getLngLat().toArray(),
    )
    const features = [
      ...this.polygonFeatures,
      ...(this.draw?.getAll().features ?? []),
    ]
    features.forEach((feature) => collectPositions(feature.geometry, positions))
    return positions
  }

  isEmpty() {
    return this.contentPositions().length === 0
  }

  // Move the camera so that all `positions` ([lng, lat]) are visible: a single
  // point is centered at `singleZoom`, none shows the whole world. Instant while
  // the map is still hidden (nobody sees it), animated once it is visible.
  showPositions(positions, singleZoom = SINGLE_MARKER_ZOOM) {
    this.map.flyTo({
      ...this.cameraFor(positions, singleZoom),
      duration: CAMERA_DURATION_MS,
      animate: this.revealed,
    })
    // The canvas only repaints on the next frame, so revealing now would
    // briefly show the previous view. Wait until the new one is rendered.
    if (!this.revealed) this.map.once("idle", () => this.reveal())
  }

  cameraFor(positions, singleZoom) {
    if (!positions.length) return { center: [0, 0], zoom: WORLD_ZOOM }
    const bounds = positions.reduce(
      (bounds, position) => bounds.extend(position),
      new maplibregl.LngLatBounds(positions[0], positions[0]),
    )
    const sw = bounds.getSouthWest()
    const ne = bounds.getNorthEast()
    if (sw.lng === ne.lng && sw.lat === ne.lat) {
      return { center: positions[0], zoom: singleZoom }
    }
    return this.map.cameraForBounds(bounds, {
      padding: 48,
      maxZoom: MAX_FIT_ZOOM,
    })
  }

  fitToContent() {
    this.whenReady(() => {
      const positions = this.contentPositions()
      if (positions.length) this.showPositions(positions)
    })
  }

  // Fit once per frame, so adding many markers/polygons in one tick only
  // triggers a single camera move.
  scheduleFit() {
    if (this.fitScheduled) return
    this.fitScheduled = true
    requestAnimationFrame(() => {
      this.fitScheduled = false
      this.fitToContent()
    })
  }

  // Back to the view the map opened with: its content, or its start view when
  // it has none.
  resetView() {
    if (this.isEmpty()) {
      this.showStartView()
    } else {
      this.fitToContent()
    }
  }

  // The view for a map without content: the `countryArea`, otherwise the whole
  // world. Skipped if content arrived meanwhile, so it never pulls the map away
  // from what it should show.
  async showStartView() {
    const area = (await this.countryArea?.()) || []
    this.whenReady(() => {
      if (this.isEmpty()) this.showPositions(area)
    })
  }

  // Show `features` as filled polygons with a floating label each, replacing
  // the ones shown before. `color` and `label` derive those from a feature.
  renderPolygons(features, { color, label, onClick }) {
    this.polygonFeatures = features.map((feature) => ({
      ...feature,
      properties: {
        ...feature.properties,
        _fillColor: color(feature),
        _label: label(feature),
      },
    }))
    this.whenReady(() => {
      const data = { type: "FeatureCollection", features: this.polygonFeatures }
      const source = this.map.getSource(POLYGON_SOURCE)
      if (source) {
        source.setData(data)
      } else {
        this.addPolygonLayers(data, onClick)
      }
      this.scheduleFit()
    })
  }

  addPolygonLayers(data, onClick) {
    this.map.addSource(POLYGON_SOURCE, { type: "geojson", data })
    this.map.addLayer({
      id: POLYGON_FILL_LAYER,
      type: "fill",
      source: POLYGON_SOURCE,
      paint: { "fill-color": ["get", "_fillColor"], "fill-opacity": 0.2 },
    })
    this.map.addLayer({
      id: POLYGON_LINE_LAYER,
      type: "line",
      source: POLYGON_SOURCE,
      paint: { "line-color": ["get", "_fillColor"], "line-width": 3 },
    })
    this.map.addLayer({
      id: POLYGON_LABEL_LAYER,
      type: "symbol",
      source: POLYGON_SOURCE,
      layout: {
        "text-field": ["get", "_label"],
        "text-font": ["Noto Sans Bold"],
        "text-size": 13,
      },
      paint: {
        "text-color": "#1a1a1a",
        "text-halo-color": "#ffffff",
        "text-halo-width": 1.5,
      },
    })
    if (!onClick) return
    this.map.on("mouseenter", POLYGON_FILL_LAYER, () => {
      this.map.getCanvas().style.cursor = "pointer"
    })
    this.map.on("mouseleave", POLYGON_FILL_LAYER, () => {
      this.map.getCanvas().style.cursor = ""
    })
    this.map.on("click", POLYGON_FILL_LAYER, (event) =>
      onClick(event.features[0]),
    )
  }

  // Add a marker. With `iconUrl` the marker shows that image; otherwise it is a
  // plain MapLibre pin. `tooltipHtml` binds a hover popup. An `editable` marker
  // can only be moved inside the location edit mode (see enableLocationEditing),
  // which hands `data` back with its new position.
  addMarker({
    group = "default",
    lat,
    lon,
    iconUrl,
    tooltipHtml,
    draggable = false,
    editable = false,
    data,
    onClick,
    onDragEnd,
  }) {
    const marker = new maplibregl.Marker(
      iconUrl
        ? { element: iconElement(iconUrl), anchor: "bottom", draggable }
        : { draggable },
    )
      .setLngLat([Number(lon), Number(lat)])
      .addTo(this.map)
    const element = marker.getElement()
    element.style.cursor = "pointer"

    if (tooltipHtml) {
      const popup = new maplibregl.Popup({
        closeButton: false,
        closeOnClick: false,
        offset: 24,
      }).setHTML(tooltipHtml)
      element.addEventListener("mouseenter", () =>
        popup.setLngLat(marker.getLngLat()).addTo(this.map),
      )
      element.addEventListener("mouseleave", () => popup.remove())
    }

    if (onClick) {
      // While editing locations a click (including the one that ends a drag)
      // must not navigate away from the map.
      element.addEventListener("click", () => {
        if (!this.locationEditControl?.editing) onClick()
      })
    }

    if (onDragEnd) {
      marker.on("dragend", () => {
        const { lng, lat } = marker.getLngLat()
        onDragEnd({ lat, lon: lng })
      })
    }

    this.markers.push({ marker, group, editable, data })
    this.scheduleFit()
    return marker
  }

  // Remove the markers of `group`, or all markers when no group is given.
  clearMarkers(group) {
    const matches = (record) => group === undefined || record.group === group
    this.markers.filter(matches).forEach(({ marker }) => marker.remove())
    this.markers = this.markers.filter((record) => !matches(record))
  }

  // A checkbox per marker group that shows/hides that group's markers.
  addMarkerGroupToggle(labelByGroup) {
    const onToggle = (group, visible) =>
      this.markers
        .filter((record) => record.group === group)
        .forEach(({ marker }) => {
          marker.getElement().style.display = visible ? "" : "none"
        })
    this.map.addControl(
      new MarkerGroupToggleControl({ labelByGroup, onToggle }),
      "top-right",
    )
  }

  // Put the editable markers behind an edit/save/cancel control. `onSave`
  // receives one change per moved marker: { data, lat, lon, revert }.
  enableLocationEditing({ labels, onSave }) {
    if (this.locationEditControl) return
    this.locationEditControl = new LocationEditControl({
      labels,
      onSave,
      records: () => this.markers.filter((record) => record.editable),
    })
    this.map.addControl(this.locationEditControl, "top-right")
  }

  // Polygon drawing/editing with mapbox-gl-draw's default look.
  enableDraw({ polygon = false, trash = false } = {}) {
    this.whenReady(() => {
      if (this.draw) return
      this.draw = new MapboxDraw({
        displayControlsDefault: false,
        controls: { polygon, trash },
      })
      this.map.addControl(this.draw, "top-right")
    })
  }

  // Listen to a mapbox-gl-draw event ("create", "update" or "delete").
  onDraw(event, callback) {
    this.map.on(`draw.${event}`, (drawEvent) => callback(drawEvent.features))
  }

  addDrawFeature(geometry) {
    this.whenReady(() => {
      this.draw.add({ type: "Feature", properties: {}, geometry })
      this.scheduleFit()
    })
  }

  clearDraw() {
    this.whenReady(() => this.draw?.deleteAll())
  }

  getDrawnPolygonGeometry() {
    const polygon = this.draw
      ?.getAll()
      .features.find((feature) => feature.geometry.type === "Polygon")
    return polygon ? polygon.geometry : null
  }
}
