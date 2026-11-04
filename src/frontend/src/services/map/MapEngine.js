import MapboxDraw from "@mapbox/mapbox-gl-draw"
import "@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css"
import * as maplibregl from "maplibre-gl"
import "maplibre-gl/dist/maplibre-gl.css"

import "./draw-cursors.css"

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

// Framework-agnostic wrapper around MapLibre GL JS and mapbox-gl-draw. Owns a
// single map instance and exposes the drawing, marker and polygon operations the
// MPM map components need. Coordinates crossing this boundary are [lat, lon]
// (the convention used throughout the app); conversion to MapLibre's [lng, lat]
// happens internally.
export class MapEngine {
  constructor(containerId, { center, zoom, maxZoom = 20 } = {}) {
    this.map = new maplibregl.Map({
      container: containerId,
      style: BASEMAP_STYLE,
      center: this.toLngLat(center),
      zoom,
      maxZoom,
    })
    this.map.addControl(
      new maplibregl.NavigationControl({ showCompass: false }),
    )

    this.loaded = false
    this.readyQueue = []
    this.markers = []
    this.draw = null
    this.drawHandlers = {}

    this.map.on("load", () => {
      this.loaded = true
      this.readyQueue.forEach((fn) => fn())
      this.readyQueue = []
    })
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

  focus(latLon, zoom) {
    this.whenReady(() =>
      this.map.jumpTo({ center: this.toLngLat(latLon), zoom }),
    )
  }

  flyTo(latLon, zoom) {
    this.whenReady(() =>
      this.map.flyTo({ center: this.toLngLat(latLon), zoom }),
    )
  }

  onMove(callback) {
    this.map.on("move", () => {
      const center = this.map.getCenter()
      callback({
        lat: center.lat,
        lng: center.lng,
        zoom: Math.round(this.map.getZoom()),
      })
    })
  }

  getCenterZoom() {
    const center = this.map.getCenter()
    return {
      lat: center.lat.toFixed(5),
      lng: center.lng.toFixed(5),
      zoom: Math.round(this.map.getZoom()),
    }
  }

  fitFeatures(features) {
    const list = Array.isArray(features) ? features : [features]
    const positions = []
    list.forEach((feature) => collectPositions(feature?.geometry, positions))
    if (!positions.length) return
    const bounds = positions.reduce(
      (bounds, position) => bounds.extend(position),
      new maplibregl.LngLatBounds(positions[0], positions[0]),
    )
    this.whenReady(() =>
      this.map.fitBounds(bounds, { padding: 40, maxZoom: 15 }),
    )
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
  // wire the usual interactions.
  addMarker({
    group = "default",
    lat,
    lon,
    iconUrl,
    tooltipHtml,
    draggable = false,
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
        if (dragged) {
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
