import axios from 'axios'

export const baseUrl = window.location.origin

const axiosClient = axios.create(

)

axiosClient.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token')
        if (token) {
            config.headers['Authorization'] = 'Bearer ' + token
        }
        return config
    },
    error => {
        Promise.reject(error)
    }
)

export default axiosClient
