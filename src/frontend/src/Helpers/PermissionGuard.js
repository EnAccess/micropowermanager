const normalizePermissions = (permissions) => {
  if (!permissions) {
    return []
  }
  return Array.isArray(permissions) ? permissions : [permissions]
}

const explicitPathRules = [
  {
    pattern: /^\/settings\/user-management/,
    permissions: ["users"],
  },
  {
    pattern: /^\/settings\/roles/,
    permissions: ["roles"],
  },
  {
    pattern: /^\/settings/,
    permissions: ["settings"],
  },
  {
    pattern: /^\/locations\/add-/,
    permissions: ["settings"],
  },
  {
    pattern: /^\/connection-groups/,
    permissions: ["settings"],
  },
  {
    pattern: /^\/connection-types/,
    permissions: ["settings"],
  },
  {
    pattern: /^\/commissions/,
    permissions: ["settings"],
  },
  {
    pattern: /^\/tariffs/,
    permissions: ["settings"],
  },
  {
    pattern: /^\/targets/,
    permissions: ["settings"],
  },
  {
    pattern: /^\/transactions/,
    permissions: ["transactions"],
  },
  {
    pattern: /^\/reports/,
    permissions: ["reports"],
  },
]

const isPluginMeta = (meta = {}) =>
  Boolean(meta?.sidebar?.enabled_by_mpm_plugin_id)

export const getPermissionsForPath = (path = "", meta = {}) => {
  if (meta?.permissions) {
    return normalizePermissions(meta.permissions)
  }
  if (isPluginMeta(meta)) {
    return ["plugins"]
  }
  const normalizedPath = (path || "").toLowerCase()
  const rule = explicitPathRules.find((entry) =>
    entry.pattern.test(normalizedPath),
  )
  return rule ? rule.permissions : []
}

export const getPermissionsForRoute = (route) => {
  if (!route) {
    return []
  }

  if (route.meta?.permissions) {
    return normalizePermissions(route.meta.permissions)
  }

  const matchedRecords = Array.isArray(route.matched) ? route.matched : []
  for (const record of matchedRecords) {
    if (record.meta?.permissions) {
      return normalizePermissions(record.meta.permissions)
    }
    if (isPluginMeta(record.meta)) {
      return ["plugins"]
    }
  }

  if (isPluginMeta(route.meta)) {
    return ["plugins"]
  }

  return getPermissionsForPath(route.path, route.meta ?? {})
}

export const userHasPermissions = (
  userPermissions = [],
  requiredPermissions = [],
) => {
  if (!requiredPermissions.length) {
    return true
  }
  if (!userPermissions.length) {
    return false
  }
  return requiredPermissions.every((permission) =>
    userPermissions.includes(permission),
  )
}
