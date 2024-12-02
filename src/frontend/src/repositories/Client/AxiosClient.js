import axios from "axios"

export const baseUrl = import.meta.env.MPM_BACKEND_URL

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
