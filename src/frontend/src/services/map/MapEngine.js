import MapboxDraw from "@mapbox/mapbox-gl-draw"
import "@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css"
import * as maplibregl from "maplibre-gl"
import "maplibre-gl/dist/maplibre-gl.css"

import "./draw-cursors.css"

// maplibre-gl starts its tile worker from a separate file whose name it builds
// at runtime, so webpack never emits it. Referencing it via `new URL(...,
// import.meta.url)` makes webpack emit the file, and we point maplibre at it.
maplibregl.setWorkerUrl(
  new URL("maplibre-gl/dist/maplibre-gl-worker.mjs", import.meta.url).href,
)

// mapbox-gl-draw ships with Mapbox CSS class names; re-point them at MapLibre's
// classes so its toolbar, canvas and cursors line up with MapLibre.
if (MapboxDraw.constants?.classes) {
  MapboxDraw.constants.classes.CANVAS = "maplibregl-canvas"
  MapboxDraw.constants.classes.CONTROL_BASE = "maplibregl-ctrl"
  MapboxDraw.constants.classes.CONTROL_PREFIX = "maplibregl-ctrl-"
  MapboxDraw.constants.classes.CONTROL_GROUP = "maplibregl-ctrl-group"
  MapboxDraw.constants.classes.ATTRIBUTION = "maplibregl-ctrl-attrib"
}

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

// Build the <img> element used for an image marker.
const iconElement = (iconUrl) => {
  const element = document.createElement("img")
  element.src = iconUrl
  element.style.width = `${ICON_MARKER_SIZE}px`
  element.style.height = `${ICON_MARKER_SIZE}px`
  return element
}

// Keyless OpenFreeMap vector basemap.
const BASEMAP_STYLE = "https://tiles.openfreemap.org/styles/bright"

// Extract every [lng, lat] position from a GeoJSON geometry so a bounding box
// can be computed without pulling in a geometry library.
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

// The standard mapbox-gl-draw theme. Passing it explicitly is required for the
// draw layers to render correctly on MapLibre; it is the same style array the
// official MapLibre "draw polygon with mapbox-gl-draw" example ships.
const DRAW_STYLES = [
  {
    id: "gl-draw-polygon-fill-inactive",
    type: "fill",
    filter: [
      "all",
      ["==", "active", "false"],
      ["==", "$type", "Polygon"],
      ["!=", "mode", "static"],
    ],
    paint: {
      "fill-color": "#3bb2d0",
      "fill-outline-color": "#3bb2d0",
      "fill-opacity": 0.1,
    },
  },
  {
    id: "gl-draw-polygon-fill-active",
    type: "fill",
    filter: ["all", ["==", "active", "true"], ["==", "$type", "Polygon"]],
    paint: {
      "fill-color": "#fbb03b",
      "fill-outline-color": "#fbb03b",
      "fill-opacity": 0.1,
    },
  },
  {
    id: "gl-draw-polygon-midpoint",
    type: "circle",
    filter: ["all", ["==", "$type", "Point"], ["==", "meta", "midpoint"]],
    paint: { "circle-radius": 3, "circle-color": "#fbb03b" },
  },
  {
    id: "gl-draw-polygon-stroke-inactive",
    type: "line",
    filter: [
      "all",
      ["==", "active", "false"],
      ["==", "$type", "Polygon"],
      ["!=", "mode", "static"],
    ],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: { "line-color": "#3bb2d0", "line-width": 2 },
  },
  {
    id: "gl-draw-polygon-stroke-active",
    type: "line",
    filter: ["all", ["==", "active", "true"], ["==", "$type", "Polygon"]],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: {
      "line-color": "#fbb03b",
      "line-dasharray": [0.2, 2],
      "line-width": 2,
    },
  },
  {
    id: "gl-draw-line-inactive",
    type: "line",
    filter: [
      "all",
      ["==", "active", "false"],
      ["==", "$type", "LineString"],
      ["!=", "mode", "static"],
    ],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: { "line-color": "#3bb2d0", "line-width": 2 },
  },
  {
    id: "gl-draw-line-active",
    type: "line",
    filter: ["all", ["==", "$type", "LineString"], ["==", "active", "true"]],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: {
      "line-color": "#fbb03b",
      "line-dasharray": [0.2, 2],
      "line-width": 2,
    },
  },
  {
    id: "gl-draw-polygon-and-line-vertex-stroke-inactive",
    type: "circle",
    filter: [
      "all",
      ["==", "meta", "vertex"],
      ["==", "$type", "Point"],
      ["!=", "mode", "static"],
    ],
    paint: { "circle-radius": 5, "circle-color": "#fff" },
  },
  {
    id: "gl-draw-polygon-and-line-vertex-inactive",
    type: "circle",
    filter: [
      "all",
      ["==", "meta", "vertex"],
      ["==", "$type", "Point"],
      ["!=", "mode", "static"],
    ],
    paint: { "circle-radius": 3, "circle-color": "#fbb03b" },
  },
  {
    id: "gl-draw-point-point-stroke-inactive",
    type: "circle",
    filter: [
      "all",
      ["==", "active", "false"],
      ["==", "$type", "Point"],
      ["==", "meta", "feature"],
      ["!=", "mode", "static"],
    ],
    paint: { "circle-radius": 5, "circle-opacity": 1, "circle-color": "#fff" },
  },
  {
    id: "gl-draw-point-inactive",
    type: "circle",
    filter: [
      "all",
      ["==", "active", "false"],
      ["==", "$type", "Point"],
      ["==", "meta", "feature"],
      ["!=", "mode", "static"],
    ],
    paint: { "circle-radius": 3, "circle-color": "#3bb2d0" },
  },
  {
    id: "gl-draw-point-stroke-active",
    type: "circle",
    filter: [
      "all",
      ["==", "$type", "Point"],
      ["==", "active", "true"],
      ["!=", "meta", "midpoint"],
    ],
    paint: { "circle-radius": 7, "circle-color": "#fff" },
  },
  {
    id: "gl-draw-point-active",
    type: "circle",
    filter: [
      "all",
      ["==", "$type", "Point"],
      ["!=", "meta", "midpoint"],
      ["==", "active", "true"],
    ],
    paint: { "circle-radius": 5, "circle-color": "#fbb03b" },
  },
  {
    id: "gl-draw-polygon-fill-static",
    type: "fill",
    filter: ["all", ["==", "mode", "static"], ["==", "$type", "Polygon"]],
    paint: {
      "fill-color": "#404040",
      "fill-outline-color": "#404040",
      "fill-opacity": 0.1,
    },
  },
  {
    id: "gl-draw-polygon-stroke-static",
    type: "line",
    filter: ["all", ["==", "mode", "static"], ["==", "$type", "Polygon"]],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: { "line-color": "#404040", "line-width": 2 },
  },
  {
    id: "gl-draw-line-static",
    type: "line",
    filter: ["all", ["==", "mode", "static"], ["==", "$type", "LineString"]],
    layout: { "line-cap": "round", "line-join": "round" },
    paint: { "line-color": "#404040", "line-width": 2 },
  },
  {
    id: "gl-draw-point-static",
    type: "circle",
    filter: ["all", ["==", "mode", "static"], ["==", "$type", "Point"]],
    paint: { "circle-radius": 5, "circle-color": "#404040" },
  },
]

const POLYGON_SOURCE = "mpm-polygons"
const POLYGON_FILL_LAYER = "mpm-polygons-fill"
const POLYGON_LINE_LAYER = "mpm-polygons-line"
const POLYGON_LABEL_LAYER = "mpm-polygons-label"

// A MapLibre IControl that toggles the visibility of named marker groups via
// checkboxes (an overlay/layer switcher).
class MarkerGroupToggleControl {
  constructor(engine, labelByGroup) {
    this.engine = engine
    this.labelByGroup = labelByGroup
  }

  onAdd() {
    const container = document.createElement("div")
    container.className =
      "maplibregl-ctrl maplibregl-ctrl-group mpm-layer-toggle"
    for (const [group, label] of Object.entries(this.labelByGroup)) {
      const row = document.createElement("label")
      row.className = "mpm-layer-toggle__row"
      const checkbox = document.createElement("input")
      checkbox.type = "checkbox"
      checkbox.checked = true
      checkbox.addEventListener("change", () =>
        this.engine.setMarkerGroupVisible(group, checkbox.checked),
      )
      row.appendChild(checkbox)
      row.appendChild(document.createTextNode(label))
      container.appendChild(row)
    }
    this.container = container
    return container
  }

  onRemove() {
    this.container?.parentNode?.removeChild(this.container)
  }
}

// Material Design icon paths for our map control buttons.
const CONTROL_ICONS = {
  reset: {
    color: "#333333",
    path: "M17 4h3c1.1 0 2 .9 2 2v2h-2V6h-3V4zM4 8V6h3V4H4c-1.1 0-2 .9-2 2v2h2zm16 8v2h-3v2h3c1.1 0 2-.9 2-2v-2h-2zM7 18H4v-2H2v2c0 1.1.9 2 2 2h3v-2zM18 8H6v8h12V8z",
  },
  edit: {
    color: "#333333",
    path: "M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z",
  },
  save: {
    color: "#2e7d32",
    path: "M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z",
  },
  cancel: {
    color: "#c62828",
    path: "M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z",
  },
}

// A square icon button styled like MapLibre's own controls; `label` is its
// tooltip and accessible name.
const iconButton = (kind, label, onClick) => {
  const { path, color } = CONTROL_ICONS[kind]
  const button = document.createElement("button")
  button.type = "button"
  button.title = label
  button.setAttribute("aria-label", label)
  button.innerHTML = `<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" style="display: block; margin: auto"><path fill="${color}" d="${path}"/></svg>`
  button.addEventListener("click", onClick)
  return button
}

// A MapLibre IControl grouping "locate me" (MapLibre's GeolocateControl, set up
// as in its "locate the user" example) with "reset view", which re-runs the
// positioning the map opened with.
class LocateResetControl {
  constructor(engine, resetLabel) {
    this.engine = engine
    this.resetLabel = resetLabel
    this.geolocate = new maplibregl.GeolocateControl({
      positionOptions: { enableHighAccuracy: true },
      trackUserLocation: true,
      fitBoundsOptions: { maxZoom: MAX_FIT_ZOOM, duration: CAMERA_DURATION_MS },
    })
  }

  // GeolocateControl creates its group and button synchronously, so the reset
  // button lands right after it in the same group.
  onAdd(map) {
    const container = this.geolocate.onAdd(map)
    container.appendChild(
      iconButton("reset", this.resetLabel, () => this.engine.resetView()),
    )
    return container
  }

  onRemove() {
    this.geolocate.onRemove()
  }
}

// A MapLibre IControl that puts editable markers behind an explicit edit mode,
// so they cannot be moved by accident: "Edit" makes them draggable, "Save"
// reports every moved marker at once and "Cancel" puts them all back.
class LocationEditControl {
  constructor(engine, { labels, onSave }) {
    this.engine = engine
    this.labels = labels
    this.onSave = onSave
  }

  onAdd() {
    this.container = document.createElement("div")
    this.container.className = "maplibregl-ctrl maplibregl-ctrl-group"
    this.render()
    return this.container
  }

  onRemove() {
    this.container?.parentNode?.removeChild(this.container)
  }

  render() {
    const buttons = this.engine.editingLocations
      ? [
          this.button("save", () =>
            this.onSave(this.engine.finishLocationEditing(true)),
          ),
          this.button("cancel", () => this.engine.finishLocationEditing(false)),
        ]
      : [this.button("edit", () => this.engine.startLocationEditing())]
    this.container.replaceChildren(...buttons)
  }

  button(kind, action) {
    return iconButton(kind, this.labels[kind], () => {
      action()
      this.render()
    })
  }
}

// Framework-agnostic wrapper around MapLibre GL JS and mapbox-gl-draw. Owns a
// single map instance and exposes the drawing, marker and polygon operations the
// MPM map components need. Coordinates crossing this boundary are [lat, lon]
// (the convention used throughout the app); conversion to MapLibre's [lng, lat]
// happens internally.
export class MapEngine {
  // `countryArea` is an async function returning [[west, south], [east, north]]
  // (or null) for maps that have nothing to show; see showStartView.
  constructor(containerId, { maxZoom = 20, labels, countryArea } = {}) {
    this.map = new maplibregl.Map({
      container: containerId,
      style: BASEMAP_STYLE,
      maxZoom,
    })
    this.map.addControl(
      new maplibregl.NavigationControl({ showCompass: false }),
    )
    this.map.addControl(new LocateResetControl(this, labels.resetView))
    this.countryArea = countryArea

    this.loaded = false
    this.readyQueue = []
    this.markers = []
    this.polygonFeatures = []
    this.draw = null
    this.drawHandlers = {}
    this.fitScheduled = false
    this.locationEditControl = null
    this.locationOriginals = null

    // Keep the map invisible until it has been positioned, so a page never
    // shows an arbitrary view and then jumps. If no content arrives, the map
    // gets its start view; a map that fails to load is revealed regardless
    // instead of silently disappearing (hence started here, not on "load").
    this.revealed = false
    this.map.getContainer().style.opacity = "0"
    setTimeout(() => {
      if (this.revealed) return
      if (this.loaded && this.isEmpty()) {
        this.showStartView()
      } else {
        this.reveal()
      }
    }, EMPTY_MAP_TIMEOUT_MS)

    this.map.on("load", () => {
      this.loaded = true
      this.readyQueue.forEach((fn) => fn())
      this.readyQueue = []
    })
  }

  reveal() {
    if (this.revealed || !this.map) return
    this.revealed = true
    this.map.getContainer().style.opacity = "1"
  }

  toLngLat(latLon) {
    return latLon ? [Number(latLon[1]), Number(latLon[0])] : [0, 0]
  }

  whenReady(fn) {
    if (this.loaded) {
      fn()
    } else {
      this.readyQueue.push(fn)
    }
  }

  destroy() {
    this.markers.forEach((marker) => marker.remove())
    this.markers = []
    this.map?.remove()
    this.map = null
  }

  resize() {
    this.map?.resize()
  }

  focus(latLon) {
    this.whenReady(() => this.showPositions([this.toLngLat(latLon)]))
  }

  // Collect every [lng, lat] currently on the map: marker positions, rendered
  // polygon vertices and any drawn features.
  contentPositions() {
    const positions = []
    this.markers.forEach((marker) => {
      const { lng, lat } = marker.getLngLat()
      positions.push([lng, lat])
    })
    this.polygonFeatures.forEach((feature) =>
      collectPositions(feature.geometry, positions),
    )
    this.draw
      ?.getAll()
      .features.forEach((feature) =>
        collectPositions(feature.geometry, positions),
      )
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

  // Center/zoom the map so that everything on it is visible.
  fitToContent() {
    this.whenReady(() => {
      const positions = this.contentPositions()
      if (positions.length) this.showPositions(positions)
    })
  }

  // Back to the view the map opened with: its content, or its start view when
  // it has none.
  resetView() {
    this.whenReady(() => {
      if (this.isEmpty()) {
        this.showStartView()
      } else {
        this.fitToContent()
      }
    })
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

  renderPolygons(features, { color, onClick, label, onEachFeature } = {}) {
    this.whenReady(() => {
      const featureCollection = {
        type: "FeatureCollection",
        features: features.map((feature) => {
          if (onEachFeature) onEachFeature(feature)
          return {
            ...feature,
            properties: {
              ...feature.properties,
              _fillColor: color ? color(feature) : "#3388ff",
              _label: label ? label(feature) : "",
            },
          }
        }),
      }

      this.polygonFeatures = featureCollection.features
      const existing = this.map.getSource(POLYGON_SOURCE)
      if (existing) {
        existing.setData(featureCollection)
      } else {
        this.map.addSource(POLYGON_SOURCE, {
          type: "geojson",
          data: featureCollection,
        })
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
        // A floating label centered on each polygon (at its visual center).
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
        this.bindPolygonInteractions(onClick)
      }
      this.scheduleFit()
    })
  }

  bindPolygonInteractions(onClick) {
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

  clearPolygons() {
    this.polygonFeatures = []
    this.whenReady(() => {
      ;[POLYGON_LABEL_LAYER, POLYGON_LINE_LAYER, POLYGON_FILL_LAYER].forEach(
        (layer) => {
          if (this.map.getLayer(layer)) this.map.removeLayer(layer)
        },
      )
      if (this.map.getSource(POLYGON_SOURCE))
        this.map.removeSource(POLYGON_SOURCE)
    })
  }

  // Add a marker. With `iconUrl` the marker shows that image; otherwise it is a
  // plain MapLibre pin. `tooltipHtml` binds a hover popup and `onClick`/`onDragEnd`
  // wire the usual interactions. An `editable` marker can only be moved inside
  // the location edit mode (see enableLocationEditing); `data` is handed back
  // with its new position when the edit is saved.
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
    const marker = iconUrl
      ? new maplibregl.Marker({
          element: iconElement(iconUrl),
          anchor: "bottom",
          draggable,
        })
      : new maplibregl.Marker({ draggable })
    marker.setLngLat([Number(lon), Number(lat)]).addTo(this.map)
    marker.mpmGroup = group
    marker.mpmEditable = editable
    marker.mpmData = data

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
      let dragged = false
      marker.on("dragstart", () => {
        dragged = true
      })
      element.addEventListener("click", () => {
        // A click that ends a drag, or any click while editing locations, must
        // not navigate away from the map.
        if (dragged || this.editingLocations) {
          dragged = false
          return
        }
        onClick()
      })
    }

    if (draggable && onDragEnd) {
      marker.on("dragend", () => {
        const { lng, lat } = marker.getLngLat()
        onDragEnd({ lat, lon: lng })
      })
    }

    this.markers.push(marker)
    this.scheduleFit()
    return marker
  }

  removeMarker(marker) {
    marker.remove()
    this.markers = this.markers.filter((candidate) => candidate !== marker)
  }

  clearMarkers(group) {
    const [removed, kept] = this.markers.reduce(
      ([removed, kept], marker) => {
        if (group === undefined || marker.mpmGroup === group) {
          removed.push(marker)
        } else {
          kept.push(marker)
        }
        return [removed, kept]
      },
      [[], []],
    )
    removed.forEach((marker) => marker.remove())
    this.markers = kept
  }

  setMarkerGroupVisible(group, visible) {
    this.markers
      .filter((marker) => marker.mpmGroup === group)
      .forEach((marker) => {
        marker.getElement().style.display = visible ? "" : "none"
      })
  }

  addMarkerGroupToggle(labelByGroup) {
    this.whenReady(() =>
      this.map.addControl(
        new MarkerGroupToggleControl(this, labelByGroup),
        "top-right",
      ),
    )
  }

  // Show the location edit control. `onSave` receives one change per moved
  // marker: { data, lat, lon, revert }.
  enableLocationEditing({ labels, onSave }) {
    if (this.locationEditControl) return
    this.locationEditControl = new LocationEditControl(this, { labels, onSave })
    this.whenReady(() =>
      this.map.addControl(this.locationEditControl, "top-right"),
    )
  }

  get editingLocations() {
    return Boolean(this.locationOriginals)
  }

  startLocationEditing() {
    this.locationOriginals = new Map(
      this.markers
        .filter((marker) => marker.mpmEditable)
        .map((marker) => [marker, marker.getLngLat().toArray()]),
    )
    this.locationOriginals.forEach((original, marker) =>
      this.setMarkerMovable(marker, true),
    )
  }

  // Leave edit mode. When saving, returns the moved markers; when cancelling,
  // puts every marker back where it was.
  finishLocationEditing(save) {
    const changes = []
    this.locationOriginals.forEach((original, marker) => {
      this.setMarkerMovable(marker, false)
      const [lng, lat] = marker.getLngLat().toArray()
      if (lng === original[0] && lat === original[1]) return
      if (save) {
        changes.push({
          data: marker.mpmData,
          lat,
          lon: lng,
          revert: () => marker.setLngLat(original),
        })
      } else {
        marker.setLngLat(original)
      }
    })
    this.locationOriginals = null
    return changes
  }

  setMarkerMovable(marker, movable) {
    marker.setDraggable(movable)
    marker.getElement().style.cursor = movable ? "move" : "pointer"
  }

  // Enable polygon drawing/editing. The options mirror the draw toggles we
  // actually use: whether new polygons can be drawn and whether features can be
  // deleted.
  enableDraw({ polygon = false, trash = false } = {}) {
    this.whenReady(() => {
      if (this.draw) return
      this.draw = new MapboxDraw({
        displayControlsDefault: false,
        controls: { polygon, trash },
        styles: DRAW_STYLES,
      })
      this.map.addControl(this.draw, "top-right")
      this.map.on("draw.create", (event) =>
        this.drawHandlers.create?.(event.features),
      )
      this.map.on("draw.update", (event) =>
        this.drawHandlers.update?.(event.features),
      )
      this.map.on("draw.delete", (event) =>
        this.drawHandlers.delete?.(event.features),
      )
    })
  }

  onDraw(action, callback) {
    this.drawHandlers[action] = callback
  }

  addDrawFeature(geometry) {
    let id
    this.whenReady(() => {
      id = this.draw.add({
        type: "Feature",
        properties: {},
        geometry,
      })[0]
      this.scheduleFit()
    })
    return id
  }

  clearDraw() {
    this.whenReady(() => this.draw?.deleteAll())
  }

  getDrawnPolygonGeometry() {
    if (!this.draw) return null
    const polygon = this.draw
      .getAll()
      .features.find((feature) => feature.geometry.type === "Polygon")
    return polygon ? polygon.geometry : null
  }
}
