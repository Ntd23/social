module.exports = function (sequelize, DataTypes) {
    return sequelize.define('User_Tag_Assignments', {
        id: {
            autoIncrement: true,
            type: DataTypes.BIGINT,
            allowNull: false,
            primaryKey: true
        },
        owner_id: {
            type: DataTypes.INTEGER,
            allowNull: false,
        },
        target_user_id: {
            type: DataTypes.INTEGER,
            allowNull: false,
        },
        tag_id: {
            type: DataTypes.BIGINTEGER,
            allowNull: false,
        }
    }, {
        sequelize,
        timestamps: false,                   // hoặc true + map createdAt
        underscored: true,
        indexes: [
            {
                unique: true,
                name: 'uniq_assign',
                fields: ['owner_id', 'target_user_id', 'tag_id']
            },
            {
                name: 'idx_owner_tag',
                fields: ['owner_id', 'tag_id']
            }
        ]
    });
      return UserTagAssignments;

};