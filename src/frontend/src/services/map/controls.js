import * as maplibregl from "maplibre-gl"

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

const controlGroup = () => {
  const container = document.createElement("div")
  container.className = "maplibregl-ctrl maplibregl-ctrl-group"
  return container
}

// "Locate me" (MapLibre's GeolocateControl, set up as in its "locate the user"
// example) and "reset view" in one group.
export class LocateResetControl {
  constructor({ label, onReset, fitBoundsOptions }) {
    this.label = label
    this.onReset = onReset
    this.geolocate = new maplibregl.GeolocateControl({
      positionOptions: { enableHighAccuracy: true },
      trackUserLocation: true,
      fitBoundsOptions,
    })
  }

  // GeolocateControl creates its group and button synchronously, so the reset
  // button lands right after it in the same group.
  onAdd(map) {
    const container = this.geolocate.onAdd(map)
    container.appendChild(iconButton("reset", this.label, this.onReset))
    return container
  }

  onRemove() {
    this.geolocate.onRemove()
  }
}

// One checkbox per marker group, toggling that group's visibility.
export class MarkerGroupToggleControl {
  constructor({ labelByGroup, onToggle }) {
    this.labelByGroup = labelByGroup
    this.onToggle = onToggle
  }

  onAdd() {
    this.container = controlGroup()
    for (const [group, label] of Object.entries(this.labelByGroup)) {
      const row = document.createElement("label")
      const checkbox = document.createElement("input")
      checkbox.type = "checkbox"
      checkbox.checked = true
      checkbox.addEventListener("change", () =>
        this.onToggle(group, checkbox.checked),
      )
      row.append(checkbox, label)
      this.container.appendChild(row)
    }
    return this.container
  }

  onRemove() {
    this.container.remove()
  }
}

// Puts movable markers behind an explicit edit mode, so they cannot be moved by
// accident: "Edit" makes them draggable, "Save" reports every moved marker at
// once and "Cancel" puts them all back. `records()` returns the movable
// markers as { marker, data }; `onSave` receives one change per moved marker:
// { data, lat, lon, revert }.
export class LocationEditControl {
  constructor({ labels, records, onSave }) {
    this.labels = labels
    this.records = records
    this.onSave = onSave
    this.originals = null
  }

  get editing() {
    return Boolean(this.originals)
  }

  onAdd() {
    this.container = controlGroup()
    this.render()
    return this.container
  }

  onRemove() {
    this.container.remove()
  }

  render() {
    const buttons = this.editing
      ? [
          this.button("save", () => this.onSave(this.finish(true))),
          this.button("cancel", () => this.finish(false)),
        ]
      : [this.button("edit", () => this.start())]
    this.container.replaceChildren(...buttons)
  }

  button(kind, action) {
    return iconButton(kind, this.labels[kind], () => {
      action()
      this.render()
    })
  }

  start() {
    this.originals = new Map(
      this.records().map((record) => [
        record,
        record.marker.getLngLat().toArray(),
      ]),
    )
    this.originals.forEach((original, { marker }) => setMovable(marker, true))
  }

  // Leave edit mode. When saving, returns the moved markers; when cancelling,
  // puts every marker back where it was.
  finish(save) {
    const changes = []
    this.originals.forEach((original, { marker, data }) => {
      setMovable(marker, false)
      const [lng, lat] = marker.getLngLat().toArray()
      if (lng === original[0] && lat === original[1]) return
      if (save) {
        changes.push({
          data,
          lat,
          lon: lng,
          revert: () => marker.setLngLat(original),
        })
      } else {
        marker.setLngLat(original)
      }
    })
    this.originals = null
    return changes
  }
}

const setMovable = (marker, movable) => {
  marker.setDraggable(movable)
  marker.getElement().style.cursor = movable ? "move" : "pointer"
}
