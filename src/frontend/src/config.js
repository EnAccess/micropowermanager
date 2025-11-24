let config = {
  mpmEnv: import.meta.env.MPM_ENV,
  mpmBackendUrl: import.meta.env.MPM_BACKEND_URL,
  mpmBackendUrlExternal:
    process.env.VUE_APP_MPM_BACKEND_URL_EXTERNAL ??
    import.meta.env.MPM_BACKEND_URL,
}

export { config }
