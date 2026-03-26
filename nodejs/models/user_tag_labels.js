// models/user_tag_labels.js
module.exports = (sequelize, DataTypes) => {
  const UserTagLabels = sequelize.define('UserTagLabels', {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: false,
      autoIncrement: true,
      primaryKey: true,
    },
    owner_id: {
      type: DataTypes.INTEGER.UNSIGNED,
      allowNull: false,
    },
    name: {
      type: DataTypes.STRING(50),
      allowNull: false,
      validate: {
        len: [1, 50],
      },
    },
    color: {
      type: DataTypes.STRING(7),
      allowNull: false,
      defaultValue: '#999999',
      validate: {
        is: /^#[0-9A-Fa-f]{6}$/, // #RRGGBB
      },
    },
    created_at: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: DataTypes.NOW,
    },
  }, {
    tableName: 'user_tag_labels',   // dùng chữ thường để tránh lỗi case-sensitive
    timestamps: false,              // hoặc dùng timestamps:true + mapped fields
    underscored: true,
    indexes: [
      { unique: true, name: 'uniq_owner_name', fields: ['owner_id', 'name'] },
      { name: 'idx_owner_id', fields: ['owner_id', 'id'] }, // hỗ trợ FK composite
    ],
  });

  return UserTagLabels;
};
