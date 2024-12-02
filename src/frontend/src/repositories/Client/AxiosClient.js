import axios from "axios"

import { config } from "@/config"

export const baseUrl = config.mpmBackendUrl

const axiosClient = axios.create({
  // Set the timeout to 120 seconds (adjust as needed)
  timeout: 120000,
})

axiosClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token")
    if (token) {
      config.headers["Authorization"] = "Bearer " + token
    }
    return config
  },
  (error) => {
    Promise.reject(error)
  },
)

export default axiosClient
