import axios from "axios"

import { config } from "@/config"

export const baseUrl = config.mpmBackendUrl
export const baseUrlExternal = config.mpmBackendUrlExternal

function loadCustomHeadersFromEnv() {
  const raw = process.env.VUE_APP_CUSTOM_HEADERS

  if (!raw) return {}

  try {
    const parsed = JSON.parse(raw)

    if (typeof parsed !== "object" || Array.isArray(parsed)) {
      throw new Error("CUSTOM_HEADERS_JSON must be an object")
    }

    console.log(parsed)

    return parsed
  } catch (err) {
    console.error("Failed to parse CUSTOM_HEADERS_JSON:", err.message)
    return {}
  }
}

const axiosClient = axios.create({
  baseURL: config.mpmBackendUrl,
  timeout: 120000,
})

axiosClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token")
    if (token) {
      config.headers["Authorization"] = "Bearer " + token
    }

    const customHeaders = loadCustomHeadersFromEnv()
    if (customHeaders) {
      config.headers = {
        ...config.headers,
        ...customHeaders,
      }
    }

    return config
  },
  (error) => {
    Promise.reject(error)
  },
)

export default axiosClient
